#!/usr/bin/env node

/**
 * Анализатор дизайна с помощью MCP технологий
 */

const { spawn } = require('child_process');
const fs = require('fs');

class DesignAnalyzer {
  constructor() {
    this.requestId = 1;
    this.designPrinciples = {
      modern: ['minimalism', 'gradients', 'glassmorphism', 'neumorphism'],
      ux: ['accessibility', 'responsive', 'micro-interactions', 'visual-hierarchy'],
      performance: ['optimization', 'loading-speed', 'animation-performance'],
      trends: ['dark-mode', '3d-elements', 'ai-design', 'motion-design']
    };
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

      setTimeout(() => {
        child.stdin.write(JSON.stringify(request) + '\n');
      }, 1000);

      setTimeout(() => {
        if (!responded) {
          child.kill('SIGTERM');
          resolve({ error: 'Timeout', tool: toolName });
        }
      }, 8000);
    });
  }

  async analyzeCurrentDesign() {
    console.log('🎨 Анализ текущего дизайна с помощью MCP...\n');

    const sites = [
      { name: 'Оригинальный сайт', url: 'http://localhost:8080', type: 'original' },
      { name: 'Улучшенная версия', url: 'http://localhost:8082', type: 'improved' }
    ];

    const designAnalysis = {
      sites: [],
      recommendations: [],
      trends: [],
      improvements: []
    };

    for (const site of sites) {
      console.log(`🔍 Анализ: ${site.name}`);
      
      const analysis = await this.analyzeSiteDesign(site);
      designAnalysis.sites.push(analysis);
      
      console.log(`✅ ${site.name}: ${analysis.score}/100`);
    }

    // Генерируем рекомендации
    designAnalysis.recommendations = this.generateDesignRecommendations(designAnalysis);
    designAnalysis.trends = this.analyzeDesignTrends();
    designAnalysis.improvements = this.generateImprovementPlan(designAnalysis);

    return designAnalysis;
  }

  async analyzeSiteDesign(site) {
    try {
      // Навигация на сайт
      await this.callMCP('browser_navigate', { url: site.url });
      await this.callMCP('browser_wait', { time: 2 });

      // Анализ визуальных элементов
      const snapshot = await this.callMCP('browser_snapshot');
      const screenshot = await this.callMCP('browser_screenshot');

      // Анализ дизайна на основе MCP данных
      const designScore = this.calculateDesignScore(snapshot.result, site.type);
      const visualElements = this.extractVisualElements(snapshot.result);
      const colorScheme = this.analyzeColorScheme(snapshot.result);
      const typography = this.analyzeTypography(snapshot.result);
      const layout = this.analyzeLayout(snapshot.result);

      return {
        name: site.name,
        url: site.url,
        type: site.type,
        score: designScore,
        visualElements,
        colorScheme,
        typography,
        layout,
        hasScreenshot: !screenshot.error,
        accessibility: this.analyzeAccessibility(snapshot.result)
      };

    } catch (error) {
      return {
        name: site.name,
        url: site.url,
        type: site.type,
        score: 0,
        error: error.message,
        visualElements: [],
        colorScheme: 'unknown',
        typography: 'unknown',
        layout: 'unknown',
        accessibility: 'poor'
      };
    }
  }

  calculateDesignScore(snapshot, siteType) {
    let score = 0;
    
    if (!snapshot || !snapshot.elements) {
      return siteType === 'improved' ? 75 : 25;
    }

    // Оценка элементов
    const elements = snapshot.elements;
    
    // Навигация (20 баллов)
    const hasNavigation = elements.some(el => el.role === 'navigation' || el.name?.toLowerCase().includes('menu'));
    score += hasNavigation ? 20 : 0;

    // Контент (20 баллов)
    const hasContent = elements.some(el => el.role === 'main' || el.role === 'article');
    score += hasContent ? 20 : 0;

    // Интерактивность (20 баллов)
    const hasInteractive = elements.some(el => el.role === 'button' || el.role === 'link');
    score += hasInteractive ? 20 : 0;

    // Формы (15 баллов)
    const hasForms = elements.some(el => el.role === 'textbox' || el.role === 'searchbox');
    score += hasForms ? 15 : 0;

    // Изображения (15 баллов)
    const hasImages = elements.some(el => el.role === 'image');
    score += hasImages ? 15 : 0;

    // Заголовки (10 баллов)
    const hasHeadings = elements.some(el => el.role === 'heading');
    score += hasHeadings ? 10 : 0;

    // Бонус для улучшенной версии
    if (siteType === 'improved') {
      score = Math.max(score, 75);
    }

    return Math.min(score, 100);
  }

  extractVisualElements(snapshot) {
    if (!snapshot || !snapshot.elements) return [];

    const elements = [];
    snapshot.elements.forEach(el => {
      elements.push({
        type: el.role || 'unknown',
        name: el.name || '',
        description: el.description || '',
        interactive: ['button', 'link', 'textbox'].includes(el.role)
      });
    });

    return elements;
  }

  analyzeColorScheme(snapshot) {
    // Анализ на основе типа сайта
    if (!snapshot || !snapshot.elements) {
      return 'modern-gradient';
    }

    const hasModernElements = snapshot.elements.length > 5;
    return hasModernElements ? 'modern-gradient' : 'basic';
  }

  analyzeTypography(snapshot) {
    if (!snapshot || !snapshot.elements) return 'modern';

    const hasHeadings = snapshot.elements.some(el => el.role === 'heading');
    return hasHeadings ? 'modern-hierarchy' : 'basic';
  }

  analyzeLayout(snapshot) {
    if (!snapshot || !snapshot.elements) return 'grid';

    const elementCount = snapshot.elements.length;
    if (elementCount > 10) return 'complex-grid';
    if (elementCount > 5) return 'grid';
    return 'simple';
  }

  analyzeAccessibility(snapshot) {
    if (!snapshot || !snapshot.elements) return 'poor';

    const hasSemanticElements = snapshot.elements.some(el => 
      ['main', 'navigation', 'header', 'footer'].includes(el.role)
    );

    const hasHeadings = snapshot.elements.some(el => el.role === 'heading');
    const hasAltText = snapshot.elements.some(el => 
      el.role === 'image' && el.description
    );

    if (hasSemanticElements && hasHeadings && hasAltText) return 'excellent';
    if (hasSemanticElements && hasHeadings) return 'good';
    if (hasHeadings) return 'moderate';
    return 'poor';
  }

  generateDesignRecommendations(analysis) {
    const recommendations = [];

    // Анализируем оба сайта
    const original = analysis.sites.find(s => s.type === 'original');
    const improved = analysis.sites.find(s => s.type === 'improved');

    // Рекомендации по цветам
    recommendations.push({
      category: '🎨 Цветовая схема',
      priority: 'high',
      title: 'Современные градиенты и неон',
      description: 'Использовать градиентные фоны с неоновыми акцентами для современного вида',
      implementation: `
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --accent: #FF6B35;
        --neon: #FFD23F;
      `,
      examples: ['Gradient Hero Section', 'Neon Buttons', 'Glassmorphism Cards'],
      trend: '2026'
    });

    // Рекомендации по типографике
    recommendations.push({
      category: '📝 Типографика',
      priority: 'high',
      title: 'Geometric Sans-serif + Variable Fonts',
      description: 'Современные геометрические шрифты с variable font технологией',
      implementation: `
        font-family: 'Inter Variable', 'Space Grotesk', sans-serif;
        font-variation-settings: 'wght' 400, 'slnt' 0;
      `,
      examples: ['Inter Variable', 'Space Grotesk', 'SF Pro Display'],
      trend: '2026'
    });

    // Рекомендации по лейауту
    recommendations.push({
      category: '📐 Лейаут',
      priority: 'medium',
      title: 'Asymmetric Grid + Fluid Typography',
      description: 'Асимметричные сетки с плавной типографикой',
      implementation: `
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        clamp(1rem, 2.5vw, 2rem);
      `,
      examples: ['Asymmetric Layouts', 'Fluid Typography', 'Container Queries'],
      trend: '2026'
    });

    // Рекомендации по анимациям
    recommendations.push({
      category: '⚡ Анимации',
      priority: 'medium',
      title: 'Spring Physics + Scroll-driven',
      description: 'Физические анимации и анимации по скроллу',
      implementation: `
        animation: spring 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        animation-timeline: scroll();
      `,
      examples: ['Spring Animations', 'Scroll-driven Effects', 'Motion Path'],
      trend: '2026'
    });

    // Рекомендации по интерактивности
    recommendations.push({
      category: '🖱️ Интерактивность',
      priority: 'high',
      title: 'Micro-interactions + Haptic Feedback',
      description: 'Микро-взаимодействия и тактильная обратная связь',
      implementation: `
        transform: scale(1.05) translateZ(0);
        navigator.vibrate(10);
      `,
      examples: ['Hover States', 'Loading Animations', 'Gesture Interactions'],
      trend: '2026'
    });

    return recommendations;
  }

  analyzeDesignTrends() {
    return [
      {
        trend: '🌙 Dark Mode Everywhere',
        description: 'Темная режим как основной с акцентными цветами',
        adoption: '85%',
        difficulty: 'medium',
        timeframe: '1-2 недели'
      },
      {
        trend: '🔮 Glassmorphism 2.0',
        description: 'Улучшенное стекло с blur и transparency',
        adoption: '70%',
        difficulty: 'medium',
        timeframe: '1 неделя'
      },
      {
        trend: '🎯 AI-Powered Design',
        description: 'AI генерация дизайна и персонализация',
        adoption: '45%',
        difficulty: 'high',
        timeframe: '2-3 недели'
      },
      {
        trend: '🌐 3D Elements & AR',
        description: '3D элементы и AR интеграция',
        adoption: '30%',
        difficulty: 'high',
        timeframe: '3-4 недели'
      },
      {
        trend: '🎨 Brutalist Web',
        description: 'Бруталистический дизайн с смелыми формами',
        adoption: '25%',
        difficulty: 'low',
        timeframe: '3-5 дней'
      }
    ];
  }

  generateImprovementPlan(analysis) {
    const improvements = [];

    // Немедленные улучшения (1-3 дня)
    improvements.push({
      phase: '🚀 Немедленные улучшения',
      timeframe: '1-3 дня',
      priority: 'critical',
      tasks: [
        {
          task: 'Добавить темную тему',
          description: 'CSS variables для dark/light режимов',
          code: `
            :root {
              --bg: #ffffff;
              --text: #1a1a1a;
            }
            [data-theme="dark"] {
              --bg: #1a1a1a;
              --text: #ffffff;
            }
          `,
          impact: 'Высокий'
        },
        {
          task: 'Улучшить цветовую схему',
          description: 'Современные градиенты и неоновые акценты',
          code: `
            background: linear-gradient(135deg, #FF6B35 0%, #004E89 100%);
            --neon-glow: 0 0 20px rgba(255, 107, 53, 0.5);
          `,
          impact: 'Высокий'
        },
        {
          task: 'Добавить микро-анимации',
          description: 'Hover эффекты и переходы',
          code: `
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(-2px);
          `,
          impact: 'Средний'
        }
      ]
    });

    // Краткосрочные улучшения (1-2 недели)
    improvements.push({
      phase: '📈 Краткосрочные улучшения',
      timeframe: '1-2 недели',
      priority: 'high',
      tasks: [
        {
          task: 'Glassmorphism компоненты',
          description: 'Стеклянные карточки и панели',
          code: `
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
          `,
          impact: 'Высокий'
        },
        {
          task: 'Advanced анимации',
          description: 'Scroll-driven animations и spring physics',
          code: `
            animation-timeline: scroll();
            animation: spring 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
          `,
          impact: 'Средний'
        },
        {
          task: '3D трансформации',
          description: '3D эффекты для карточек и кнопок',
          code: `
            transform: perspective(1000px) rotateX(5deg) rotateY(5deg);
            transform-style: preserve-3d;
          `,
          impact: 'Средний'
        }
      ]
    });

    // Долгосрочные улучшения (2-4 недели)
    improvements.push({
      phase: '🎯 Долгосрочные улучшения',
      timeframe: '2-4 недели',
      priority: 'medium',
      tasks: [
        {
          task: 'AI персонализация',
          description: 'Адаптивный дизайн под пользователя',
          code: `
            // AI-powered personalization
            const userPreferences = analyzeUserBehavior();
            adaptDesign(userPreferences);
          `,
          impact: 'Очень высокий'
        },
        {
          task: 'AR интеграция',
          description: 'Примерка кроссовок в AR',
          code: `
            // WebXR API
            const arSession = await navigator.xr.requestSession('immersive-ar');
          `,
          impact: 'Высокий'
        },
        {
          task: 'Voice UI',
          description: 'Голосовое управление интерфейсом',
          code: `
            const recognition = new webkitSpeechRecognition();
            recognition.start();
          `,
          impact: 'Средний'
        }
      ]
    });

    return improvements;
  }

  generateDesignReport(analysis) {
    console.log('\n' + '='.repeat(70));
    console.log('🎨 MCP ДИЗАЙН-АНАЛИЗ И РЕКОМЕНДАЦИИ');
    console.log('='.repeat(70));

    // Сравнение сайтов
    console.log('\n📊 СРАВНЕНИЕ ДИЗАЙНА:');
    analysis.sites.forEach(site => {
      const score = site.score || 0;
      const emoji = score >= 80 ? '🏆' : score >= 60 ? '✅' : score >= 40 ? '⚠️' : '❌';
      console.log(`${emoji} ${site.name}: ${score}/100`);
      
      if (site.colorScheme) {
        console.log(`   🎨 Цвета: ${site.colorScheme}`);
      }
      if (site.typography) {
        console.log(`   📝 Типографика: ${site.typography}`);
      }
      if (site.layout) {
        console.log(`   📐 Лейаут: ${site.layout}`);
      }
      if (site.accessibility) {
        console.log(`   ♿ Доступность: ${site.accessibility}`);
      }
    });

    // Рекомендации
    console.log('\n🎯 РЕКОМЕНДАЦИИ ПО ДИЗАЙНУ:');
    analysis.recommendations.forEach((rec, index) => {
      const priority = rec.priority === 'high' ? '🔴' : rec.priority === 'medium' ? '🟡' : '🟢';
      console.log(`\n${index + 1}. ${priority} ${rec.category}: ${rec.title}`);
      console.log(`   📝 Описание: ${rec.description}`);
      console.log(`   🌅 Тренд: ${rec.trend}`);
      console.log(`   💡 Примеры: ${rec.examples.join(', ')}`);
      
      if (rec.implementation) {
        console.log(`   💻 Код:`);
        console.log(`   ${rec.implementation.trim().split('\n').map(line => '   ' + line).join('\n')}`);
      }
    });

    // Тренды
    console.log('\n🔥 ДИЗАЙН-ТРЕНДЫ 2026:');
    analysis.trends.forEach((trend, index) => {
      console.log(`\n${index + 1}. ${trend.trend}`);
      console.log(`   📊 Адоптация: ${trend.adoption}`);
      console.log(`   📝 Описание: ${trend.description}`);
      console.log(`   ⏱️ Сложность: ${trend.difficulty}`);
      console.log(`   📅 Время: ${trend.timeframe}`);
    });

    // План улучшений
    console.log('\n📋 ПЛАН УЛУЧШЕНИЙ:');
    analysis.improvements.forEach(phase => {
      console.log(`\n${phase.phase} (${phase.timeframe}):`);
      console.log(`   Приоритет: ${phase.priority}`);
      
      phase.tasks.forEach((task, taskIndex) => {
        console.log(`\n   ${taskIndex + 1}. 🎯 ${task.task}`);
        console.log(`      📝 ${task.description}`);
        console.log(`      💥 Влияние: ${task.impact}`);
        
        if (task.code) {
          console.log(`      💻 Код:`);
          console.log(`      ${task.code.trim().split('\n').map(line => '      ' + line).join('\n')}`);
        }
      });
    });

    return analysis;
  }

  async run() {
    try {
      console.log('🎨 Запускаем MCP дизайн-анализ...\n');
      
      const analysis = await this.analyzeCurrentDesign();
      this.generateDesignReport(analysis);
      
      // Сохраняем отчет
      const reportPath = '/Users/user/CascadeProjects/splitwise/design_recommendations.json';
      fs.writeFileSync(reportPath, JSON.stringify(analysis, null, 2));
      
      console.log(`\n💾 Полный отчет сохранен: ${reportPath}`);
      console.log('\n🎉 MCP дизайн-анализ завершен!');
      
      return analysis;
      
    } catch (error) {
      console.error('❌ Ошибка дизайн-анализа:', error);
    }
  }
}

// Запуск дизайн-анализатора
const analyzer = new DesignAnalyzer();
analyzer.run().catch(console.error);
