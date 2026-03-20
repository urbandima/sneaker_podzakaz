#!/usr/bin/env node

/**
 * Автоматическое исследование сайта с помощью Browser MCP
 */

const { spawn } = require('child_process');
const fs = require('fs');

class SiteExplorer {
  constructor() {
    this.browserProcess = null;
    this.siteData = {
      pages: [],
      navigation: [],
      forms: [],
      buttons: [],
      structure: {}
    };
  }

  async startBrowser() {
    return new Promise((resolve) => {
      console.log('🚀 Запуск браузера...');
      
      this.browserProcess = spawn('npx', ['@browsermcp/mcp'], {
        stdio: ['pipe', 'pipe', 'pipe'],
        cwd: process.cwd()
      });

      let initialized = false;
      
      this.browserProcess.stdout.on('data', (data) => {
        const output = data.toString();
        if (output.includes('Server started') || output.includes('Starting server')) {
          initialized = true;
          console.log('✅ Браузер готов к работе');
          resolve();
        }
      });

      setTimeout(() => {
        if (!initialized) {
          console.log('⚠️ Принудительный запуск...');
          resolve();
        }
      }, 3000);
    });
  }

  async sendCommand(method, params = {}) {
    return new Promise((resolve) => {
      const request = {
        jsonrpc: "2.0",
        id: Date.now(),
        method: method,
        params: params
      };

      if (!this.browserProcess) {
        resolve({ error: 'Browser not started' });
        return;
      }

      let responded = false;
      
      const timeout = setTimeout(() => {
        if (!responded) {
          responded = true;
          resolve({ error: 'Timeout' });
        }
      }, 5000);

      this.browserProcess.stdout.on('data', (data) => {
        if (!responded) {
          try {
            const response = JSON.parse(data.toString().trim());
            if (response.id === request.id) {
              responded = true;
              clearTimeout(timeout);
              resolve(response);
            }
          } catch (e) {
            // Игнорируем ошибки парсинга
          }
        }
      });

      this.browserProcess.stdin.write(JSON.stringify(request) + '\n');
    });
  }

  async exploreHomePage() {
    console.log('🏠 Анализ главной страницы...');
    
    // Навигация на главную страницу
    const navResult = await this.sendCommand('browser_navigate', { 
      url: 'http://localhost:8080' 
    });
    
    if (navResult.error) {
      console.log('❌ Ошибка навигации:', navResult.error);
      return;
    }
    
    console.log('✅ Страница загружена');
    
    // Ожидание загрузки
    await this.sendCommand('browser_wait', { time: 2 });
    
    // Получение снапшота страницы
    const snapshot = await this.sendCommand('browser_snapshot');
    
    if (snapshot.result) {
      console.log('📸 Снапшот получен');
      this.analyzeSnapshot(snapshot.result);
    }
    
    // Скриншот для визуального анализа
    const screenshot = await this.sendCommand('browser_screenshot');
    if (screenshot.result) {
      console.log('📷 Скриншот сделан');
    }
  }

  analyzeSnapshot(snapshot) {
    console.log('🔍 Анализ структуры страницы...');
    
    // Извлечение навигации
    if (snapshot.metadata && snapshot.metadata.title) {
      this.siteData.title = snapshot.metadata.title;
      console.log(`📄 Заголовок: ${snapshot.metadata.title}`);
    }
    
    // Анализ элементов
    if (snapshot.elements) {
      snapshot.elements.forEach(element => {
        switch (element.role) {
          case 'link':
            if (element.name && element.uri) {
              this.siteData.navigation.push({
                text: element.name,
                url: element.uri
              });
            }
            break;
          case 'button':
            if (element.name) {
              this.siteData.buttons.push({
                text: element.name,
                description: element.description || ''
              });
            }
            break;
          case 'textbox':
            if (element.name) {
              this.siteData.forms.push({
                field: element.name,
                type: element.inputType || 'text'
              });
            }
            break;
        }
      });
    }
    
    console.log(`🔗 Найдено ссылок: ${this.siteData.navigation.length}`);
    console.log(`🔘 Найдено кнопок: ${this.siteData.buttons.length}`);
    console.log(`📝 Найдено полей: ${this.siteData.forms.length}`);
  }

  async exploreAllPages() {
    console.log('🔄 Исследование всех страниц...');
    
    const pagesToExplore = [
      { name: 'Каталог', url: '/catalog' },
      { name: 'Бренды', url: '/brands' },
      { name: 'Акции', url: '/sale' },
      { name: 'Вход', url: '/account/login' },
      { name: 'Регистрация', url: '/account/register' }
    ];
    
    for (const page of pagesToExplore) {
      console.log(`📄 Переход на страницу: ${page.name}`);
      
      const navResult = await this.sendCommand('browser_navigate', { 
        url: `http://localhost:8080${page.url}` 
      });
      
      if (!navResult.error) {
        await this.sendCommand('browser_wait', { time: 1 });
        
        const snapshot = await this.sendCommand('browser_snapshot');
        if (snapshot.result) {
          this.siteData.pages.push({
            name: page.name,
            url: page.url,
            accessible: true,
            elements: this.countElements(snapshot.result)
          });
        }
      } else {
        this.siteData.pages.push({
          name: page.name,
          url: page.url,
          accessible: false,
          error: navResult.error
        });
      }
    }
  }

  countElements(snapshot) {
    const counts = {
      links: 0,
      buttons: 0,
      forms: 0,
      images: 0
    };
    
    if (snapshot.elements) {
      snapshot.elements.forEach(element => {
        switch (element.role) {
          case 'link': counts.links++; break;
          case 'button': counts.buttons++; break;
          case 'textbox': counts.forms++; break;
          case 'image': counts.images++; break;
        }
      });
    }
    
    return counts;
  }

  async clickAllButtons() {
    console.log('🔘 Клик по всем кнопкам...');
    
    for (const button of this.siteData.buttons) {
      console.log(`🖱️ Клик по кнопке: ${button.text}`);
      
      // Сначала делаем снапшот для получения референсов
      const snapshot = await this.sendCommand('browser_snapshot');
      
      if (snapshot.result && snapshot.result.elements) {
        const buttonElement = snapshot.result.elements.find(
          el => el.role === 'button' && el.name === button.text
        );
        
        if (buttonElement) {
          const clickResult = await this.sendCommand('browser_click', {
            element: button.text,
            ref: buttonElement.ref || button.text
          });
          
          if (!clickResult.error) {
            console.log(`✅ Кнопка "${button.text}" нажата`);
            await this.sendCommand('browser_wait', { time: 1 });
          } else {
            console.log(`❌ Ошибка клика на "${button.text}": ${clickResult.error}`);
          }
        }
      }
    }
  }

  generateReport() {
    console.log('\n📊 АНАЛИТИЧЕСКИЙ ОТЧЕТ О САЙТЕ');
    console.log('='.repeat(50));
    
    console.log(`🏠 Главная страница: ${this.siteData.title || 'Не определена'}`);
    console.log(`🔗 Навигационных ссылок: ${this.siteData.navigation.length}`);
    console.log(`🔘 Кнопок на главной: ${this.siteData.buttons.length}`);
    console.log(`📝 Форм ввода: ${this.siteData.forms.length}`);
    
    console.log('\n📄 Доступные страницы:');
    this.siteData.pages.forEach(page => {
      const status = page.accessible ? '✅' : '❌';
      console.log(`${status} ${page.name} (${page.url})`);
      if (page.elements) {
        console.log(`   Links: ${page.elements.links}, Buttons: ${page.elements.buttons}, Forms: ${page.elements.forms}`);
      }
    });
    
    console.log('\n🔗 Навигация:');
    this.siteData.navigation.forEach(nav => {
      console.log(`   • ${nav.text} → ${nav.url}`);
    });
    
    console.log('\n🔘 Кнопки:');
    this.siteData.buttons.forEach(button => {
      console.log(`   • ${button.text}`);
    });
    
    // Сохранение отчета
    const reportPath = '/Users/user/CascadeProjects/splitwise/site_analysis_report.json';
    fs.writeFileSync(reportPath, JSON.stringify(this.siteData, null, 2));
    console.log(`\n💾 Отчет сохранен: ${reportPath}`);
  }

  async cleanup() {
    if (this.browserProcess) {
      this.browserProcess.kill('SIGTERM');
      console.log('🔚 Браузер закрыт');
    }
  }

  async run() {
    try {
      await this.startBrowser();
      await this.exploreHomePage();
      await this.exploreAllPages();
      await this.clickAllButtons();
      this.generateReport();
    } catch (error) {
      console.error('❌ Ошибка:', error);
    } finally {
      await this.cleanup();
    }
  }
}

// Запуск исследования
const explorer = new SiteExplorer();
explorer.run().catch(console.error);
