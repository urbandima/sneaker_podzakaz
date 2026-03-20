#!/usr/bin/env node

/**
 * Улучшенное исследование сайта через MCP с правильным синтаксисом
 */

const { spawn } = require('child_process');
const fs = require('fs');

class ImprovedSiteExplorer {
  constructor() {
    this.requestId = 1;
    this.responses = new Map();
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

      child.stderr.on('data', (data) => {
        console.log(`⚠️ stderr: ${data.toString().trim()}`);
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
      }, 8000);
    });
  }

  async navigateToPage(url) {
    console.log(`🌐 Переход на: ${url}`);
    
    const result = await this.callMCP('browser_navigate', { url });
    
    if (result.error) {
      console.log(`❌ Ошибка навигации: ${result.error.message || result.error}`);
      return null;
    }
    
    console.log('✅ Страница загружена');
    return result;
  }

  async takeSnapshot() {
    console.log('📸 Создание снапшота...');
    
    const result = await this.callMCP('browser_snapshot');
    
    if (result.error) {
      console.log(`❌ Ошибка снапшота: ${result.error.message || result.error}`);
      return null;
    }
    
    console.log('✅ Снапшот получен');
    return result.result;
  }

  async takeScreenshot() {
    console.log('📷 Создание скриншота...');
    
    const result = await this.callMCP('browser_screenshot');
    
    if (result.error) {
      console.log(`❌ Ошибка скриншота: ${result.error.message || result.error}`);
      return null;
    }
    
    console.log('✅ Скриншот сделан');
    return result.result;
  }

  async clickElement(elementText, elementRef = null) {
    console.log(`🖱️ Клик по: ${elementText}`);
    
    const result = await this.callMCP('browser_click', { 
      element: elementText,
      ref: elementRef || elementText 
    });
    
    if (result.error) {
      console.log(`❌ Ошибка клика: ${result.error.message || result.error}`);
      return null;
    }
    
    console.log(`✅ Клик выполнен`);
    return result;
  }

  async wait(seconds) {
    console.log(`⏱️ Ожидание ${seconds} сек...`);
    
    const result = await this.callMCP('browser_wait', { time: seconds });
    
    if (result.error) {
      console.log(`⚠️ Ошибка ожидания: ${result.error.message || result.error}`);
    }
    
    return result;
  }

  analyzePage(snapshot, pageName) {
    const analysis = {
      page: pageName,
      title: '',
      links: [],
      buttons: [],
      forms: [],
      images: [],
      headings: [],
      totalElements: 0
    };

    if (snapshot) {
      if (snapshot.metadata && snapshot.metadata.title) {
        analysis.title = snapshot.metadata.title;
      }

      if (snapshot.elements) {
        analysis.totalElements = snapshot.elements.length;
        
        snapshot.elements.forEach(element => {
          const elementInfo = {
            name: element.name || '',
            description: element.description || '',
            ref: element.ref || ''
          };

          switch (element.role) {
            case 'link':
              if (element.uri) {
                analysis.links.push({
                  ...elementInfo,
                  url: element.uri
                });
              }
              break;
            case 'button':
              analysis.buttons.push(elementInfo);
              break;
            case 'textbox':
            case 'searchbox':
              analysis.forms.push({
                ...elementInfo,
                type: element.inputType || 'text'
              });
              break;
            case 'image':
              analysis.images.push(elementInfo);
              break;
            case 'heading':
              analysis.headings.push(elementInfo);
              break;
          }
        });
      }
    }

    return analysis;
  }

  async exploreSite() {
    console.log('🚀 Начало исследования сайта...\n');

    const pages = [
      { name: 'Главная', url: 'http://localhost:8080' },
      { name: 'Каталог', url: 'http://localhost:8080/catalog' },
      { name: 'Бренды', url: 'http://localhost:8080/brands' },
      { name: 'Акции', url: 'http://localhost:8080/sale' },
      { name: 'Вход', url: 'http://localhost:8080/account/login' },
      { name: 'Регистрация', url: 'http://localhost:8080/account/register' }
    ];

    const siteReport = {
      siteName: 'Sneakerhead',
      baseUrl: 'http://localhost:8080',
      exploredAt: new Date().toISOString(),
      pages: [],
      summary: {
        totalPages: 0,
        accessiblePages: 0,
        totalLinks: 0,
        totalButtons: 0,
        totalForms: 0
      }
    };

    for (const page of pages) {
      console.log(`\n📄 Исследование страницы: ${page.name}`);
      
      const navResult = await this.navigateToPage(page.url);
      
      if (navResult && !navResult.error) {
        await this.wait(2);
        
        const snapshot = await this.takeSnapshot();
        const screenshot = await this.takeScreenshot();
        
        const analysis = this.analyzePage(snapshot, page.name);
        analysis.accessible = true;
        analysis.screenshot = screenshot ? 'taken' : 'failed';
        
        siteReport.pages.push(analysis);
        siteReport.summary.totalPages++;
        siteReport.summary.accessiblePages++;
        siteReport.summary.totalLinks += analysis.links.length;
        siteReport.summary.totalButtons += analysis.buttons.length;
        siteReport.summary.totalForms += analysis.forms.length;
        
        console.log(`📊 Ссылок: ${analysis.links.length}, Кнопок: ${analysis.buttons.length}, Форм: ${analysis.forms.length}`);
        
        // Клик по нескольким кнопкам для тестирования
        if (analysis.buttons.length > 0 && page.name === 'Главная') {
          console.log('🔘 Тестирование кнопок...');
          for (let i = 0; i < Math.min(3, analysis.buttons.length); i++) {
            const button = analysis.buttons[i];
            await this.clickElement(button.name, button.ref);
            await this.wait(1);
            
            // Возвращаемся назад
            await this.callMCP('browser_go_back');
            await this.wait(1);
          }
        }
      } else {
        siteReport.pages.push({
          page: page.name,
          url: page.url,
          accessible: false,
          error: navResult?.error?.message || 'Navigation failed'
        });
        console.log('❌ Страница недоступна');
      }
    }

    return siteReport;
  }

  generateReport(siteReport) {
    console.log('\n' + '='.repeat(60));
    console.log('📊 ПОЛНЫЙ ОТЧЕТ ОБ ИССЛЕДОВАНИИ САЙТА');
    console.log('='.repeat(60));
    
    console.log(`🏠 Сайт: ${siteReport.siteName}`);
    console.log(`🌐 Базовый URL: ${siteReport.baseUrl}`);
    console.log(`⏰ Время исследования: ${siteReport.exploredAt}`);
    
    console.log('\n📈 СВОДНАЯ СТАТИСТИКА:');
    console.log(`📄 Всего страниц: ${siteReport.summary.totalPages}`);
    console.log(`✅ Доступно страниц: ${siteReport.summary.accessiblePages}`);
    console.log(`🔗 Всего ссылок: ${siteReport.summary.totalLinks}`);
    console.log(`🔘 Всего кнопок: ${siteReport.summary.totalButtons}`);
    console.log(`📝 Всего форм: ${siteReport.summary.totalForms}`);
    
    console.log('\n📄 ДЕТАЛИ ПО СТРАНИЦАМ:');
    siteReport.pages.forEach((page, index) => {
      const status = page.accessible ? '✅' : '❌';
      console.log(`\n${index + 1}. ${status} ${page.page}`);
      
      if (page.accessible) {
        console.log(`   🔗 Заголовок: ${page.title || 'Не указан'}`);
        console.log(`   📊 Элементов: ${page.totalElements}`);
        console.log(`   🔗 Ссылок: ${page.links.length}`);
        console.log(`   🔘 Кнопок: ${page.buttons.length}`);
        console.log(`   📝 Форм: ${page.forms.length}`);
        
        if (page.links.length > 0) {
          console.log('   🔗 Основные ссылки:');
          page.links.slice(0, 5).forEach(link => {
            console.log(`      • ${link.name} → ${link.url}`);
          });
        }
        
        if (page.buttons.length > 0) {
          console.log('   🔘 Кнопки:');
          page.buttons.slice(0, 3).forEach(button => {
            console.log(`      • ${button.name}`);
          });
        }
      } else {
        console.log(`   ❌ Ошибка: ${page.error || 'Неизвестная ошибка'}`);
      }
    });
    
    // Сохранение отчета
    const reportPath = '/Users/user/CascadeProjects/splitwise/complete_site_analysis.json';
    fs.writeFileSync(reportPath, JSON.stringify(siteReport, null, 2));
    console.log(`\n💾 Полный отчет сохранен: ${reportPath}`);
    
    return siteReport;
  }

  async run() {
    try {
      const siteReport = await this.exploreSite();
      this.generateReport(siteReport);
      
      console.log('\n🎉 Исследование сайта завершено!');
      console.log('📋 Теперь можно создать улучшенную версию сайта на основе этих данных.');
      
    } catch (error) {
      console.error('❌ Критическая ошибка:', error);
    }
  }
}

// Запуск исследования
const explorer = new ImprovedSiteExplorer();
explorer.run().catch(console.error);
