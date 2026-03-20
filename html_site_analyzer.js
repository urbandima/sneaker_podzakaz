#!/usr/bin/env node

/**
 * Анализатор HTML содержимого сайта с помощью MCP
 */

const { spawn } = require('child_process');
const fs = require('fs');

class HTMLSiteAnalyzer {
  constructor() {
    this.requestId = 1;
  }

  async callMCP(toolName, params = {}) {
    return new Promise((resolve) => {
      const child = spawn('npx', ['@browsermcp/mcp'], {
        stdio: ['pipe', 'pipe', 'pipe'],
        cwd: process.cwd()
      });

      const request = {
        jsonrpc: "2.0",
        id: this.requestId++,
        method: "tools/call",
        params: {
          name: toolName,
          arguments: params
        }
      };

      let responded = false;
      
      child.stdout.on('data', (data) => {
        const output = data.toString();
        
        try {
          const lines = output.trim().split('\n');
          lines.forEach(line => {
            if (line.trim()) {
              const response = JSON.parse(line);
              if (response.id === request.id && !responded) {
                responded = true;
                child.kill('SIGTERM');
                resolve(response);
              }
            }
          });
        } catch (e) {
          // Игнорируем ошибки парсинга
        }
      });

      // Отправляем запрос после запуска
      setTimeout(() => {
        child.stdin.write(JSON.stringify(request) + '\n');
      }, 1000);

      // Таймаут
      setTimeout(() => {
        if (!responded) {
          child.kill('SIGTERM');
          resolve({ error: 'Timeout', tool: toolName });
        }
      }, 10000);
    });
  }

  async getPageContent(url) {
    console.log(`🌐 Загрузка страницы: ${url}`);
    
    // Навигация
    const navResult = await this.callMCP('browser_navigate', { url });
    if (navResult.error) {
      console.log(`❌ Ошибка навигации: ${navResult.error.message || navResult.error}`);
      return null;
    }
    
    // Ожидание загрузки
    await this.callMCP('browser_wait', { time: 3 });
    
    // Получение HTML через JavaScript (если доступно)
    try {
      // Попробуем получить контент через snapshot
      const snapshot = await this.callMCP('browser_snapshot');
      
      // Сделаем скриншот для визуального анализа
      const screenshot = await this.callMCP('browser_screenshot');
      
      return {
        url: url,
        snapshot: snapshot.result,
        screenshot: screenshot.result,
        accessible: !navResult.error
      };
    } catch (error) {
      console.log(`⚠️ Ошибка получения контента: ${error.message}`);
      return {
        url: url,
        error: error.message,
        accessible: false
      };
    }
  }

  async analyzeSite() {
    console.log('🚀 Анализ сайта Sneakerhead...\n');

    const pages = [
      { name: 'Главная', url: 'http://localhost:8080', type: 'homepage' },
      { name: 'Каталог', url: 'http://localhost:8080/catalog', type: 'catalog' },
      { name: 'Бренды', url: 'http://localhost:8080/brands', type: 'brands' },
      { name: 'Акции', url: 'http://localhost:8080/sale', type: 'sale' },
      { name: 'Вход', url: 'http://localhost:8080/account/login', type: 'auth' },
      { name: 'Регистрация', url: 'http://localhost:8080/account/register', type: 'auth' }
    ];

    const siteAnalysis = {
      siteName: 'Sneakerhead',
      baseUrl: 'http://localhost:8080',
      analyzedAt: new Date().toISOString(),
      pages: [],
      features: {
        hasAuth: false,
        hasCatalog: false,
        hasSearch: false,
        hasCart: false,
        hasResponsive: false,
        hasModernUI: false
      },
      improvements: []
    };

    for (const page of pages) {
      console.log(`📄 Анализ: ${page.name}`);
      
      const content = await this.getPageContent(page.url);
      
      const pageAnalysis = {
        name: page.name,
        url: page.url,
        type: page.type,
        accessible: content.accessible,
        hasContent: content.snapshot ? true : false,
        hasScreenshot: content.screenshot ? true : false,
        elements: this.countElements(content.snapshot),
        recommendations: this.getRecommendations(page.type, content)
      };
      
      siteAnalysis.pages.push(pageAnalysis);
      
      // Обновляем фичи
      if (page.type === 'auth' && content.accessible) {
        siteAnalysis.features.hasAuth = true;
      }
      if (page.type === 'catalog' && content.accessible) {
        siteAnalysis.features.hasCatalog = true;
      }
      
      console.log(`✅ ${page.name}: ${content.accessible ? 'Доступна' : 'Недоступна'}`);
    }

    // Генерируем рекомендации по улучшению
    siteAnalysis.improvements = this.generateImprovements(siteAnalysis);
    
    return siteAnalysis;
  }

  countElements(snapshot) {
    if (!snapshot || !snapshot.elements) {
      return { total: 0, links: 0, buttons: 0, forms: 0, images: 0 };
    }
    
    const counts = { total: 0, links: 0, buttons: 0, forms: 0, images: 0 };
    
    snapshot.elements.forEach(element => {
      counts.total++;
      switch (element.role) {
        case 'link': counts.links++; break;
        case 'button': counts.buttons++; break;
        case 'textbox':
        case 'searchbox': counts.forms++; break;
        case 'image': counts.images++; break;
      }
    });
    
    return counts;
  }

  getRecommendations(pageType, content) {
    const recommendations = [];
    
    if (!content.accessible) {
      recommendations.push('Исправить ошибки загрузки страницы');
      return recommendations;
    }
    
    if (!content.snapshot || content.snapshot.elements.length === 0) {
      recommendations.push('Добавить семантическую разметку для accessibility');
      recommendations.push('Улучшить HTML структуру');
    }
    
    switch (pageType) {
      case 'homepage':
        recommendations.push('Добавить hero секцию с призывом к действию');
        recommendations.push('Показать популярные товары');
        recommendations.push('Добавить отзывы клиентов');
        break;
      case 'catalog':
        recommendations.push('Добавить фильтры и сортировку');
        recommendations.push('Реализовать сетку товаров с карточками');
        recommendations.push('Добавить пагинацию');
        break;
      case 'brands':
        recommendations.push('Создать алфавитный указатель брендов');
        recommendations.push('Добавить логотипы брендов');
        break;
      case 'sale':
        recommendations.push('Добавить таймеры акций');
        recommendations.push('Создать привлекательные баннеры');
        break;
      case 'auth':
        recommendations.push('Улучшить UX форм входа/регистрации');
        recommendations.push('Добавить социальную авторизацию');
        break;
    }
    
    return recommendations;
  }

  generateImprovements(siteAnalysis) {
    const improvements = [];
    
    // Общие улучшения
    improvements.push({
      priority: 'high',
      category: 'UI/UX',
      title: 'Создать современный дизайн',
      description: 'Разработать современный, отзывчивый дизайн с использованием TailwindCSS',
      estimatedTime: '2-3 дня'
    });
    
    improvements.push({
      priority: 'high',
      category: 'Navigation',
      title: 'Улучшить навигацию',
      description: 'Создать удобное меню с поиском и корзиной',
      estimatedTime: '1 день'
    });
    
    improvements.push({
      priority: 'medium',
      category: 'Performance',
      title: 'Оптимизировать производительность',
      description: 'Добавить кэширование, оптимизировать изображения',
      estimatedTime: '1-2 дня'
    });
    
    improvements.push({
      priority: 'medium',
      category: 'Features',
      title: 'Добавить поиск товаров',
      description: 'Реализовать полнотекстовый поиск с фильтрами',
      estimatedTime: '2 дня'
    });
    
    improvements.push({
      priority: 'low',
      category: 'SEO',
      title: 'Улучшить SEO оптимизацию',
      description: 'Добавить мета-теги, микроразметку, sitemap',
      estimatedTime: '1 день'
    });
    
    return improvements;
  }

  generateReport(analysis) {
    console.log('\n' + '='.repeat(60));
    console.log('📊 АНАЛИЗ И ПЛАН УЛУЧШЕНИЯ САЙТА SNEAKERHEAD');
    console.log('='.repeat(60));
    
    console.log(`🏠 Сайт: ${analysis.siteName}`);
    console.log(`🌐 URL: ${analysis.baseUrl}`);
    console.log(`⏰ Время анализа: ${analysis.analyzedAt}`);
    
    console.log('\n🔍 АНАЛИЗ СТРАНИЦ:');
    analysis.pages.forEach((page, index) => {
      const status = page.accessible ? '✅' : '❌';
      console.log(`\n${index + 1}. ${status} ${page.name} (${page.type})`);
      console.log(`   📊 Элементы: ${page.elements.total}`);
      console.log(`   📸 Скриншот: ${page.hasScreenshot ? '✅' : '❌'}`);
      
      if (page.recommendations.length > 0) {
        console.log('   💡 Рекомендации:');
        page.recommendations.forEach(rec => {
          console.log(`      • ${rec}`);
        });
      }
    });
    
    console.log('\n🎯 ПЛАН УЛУЧШЕНИЙ:');
    analysis.improvements.forEach((improvement, index) => {
      const priority = improvement.priority === 'high' ? '🔴' : 
                      improvement.priority === 'medium' ? '🟡' : '🟢';
      console.log(`\n${index + 1}. ${priority} ${improvement.title}`);
      console.log(`   📂 Категория: ${improvement.category}`);
      console.log(`   ⏱️ Время: ${improvement.estimatedTime}`);
      console.log(`   📝 Описание: ${improvement.description}`);
    });
    
    // Сохранение анализа
    const analysisPath = '/Users/user/CascadeProjects/splitwise/site_improvement_plan.json';
    fs.writeFileSync(analysisPath, JSON.stringify(analysis, null, 2));
    console.log(`\n💾 Анализ сохранен: ${analysisPath}`);
    
    return analysis;
  }

  async run() {
    try {
      console.log('🔬 Запускаем глубокий анализ сайта...\n');
      
      const analysis = await this.analyzeSite();
      this.generateReport(analysis);
      
      console.log('\n🎯 Теперь создадим улучшенную версию сайта!');
      console.log('📋 Готовим "наличку" - современный дизайн...');
      
      return analysis;
      
    } catch (error) {
      console.error('❌ Ошибка анализа:', error);
    }
  }
}

// Запуск анализатора
const analyzer = new HTMLSiteAnalyzer();
analyzer.run().catch(console.error);
