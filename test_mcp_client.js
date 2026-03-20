#!/usr/bin/env node

/**
 * Тестовый MCP клиент для проверки серверов
 */

const { spawn } = require('child_process');
const path = require('path');

class MCPTester {
  constructor() {
    this.servers = {
      'browser-mcp': {
        command: 'npx',
        args: ['@sanity-labs/browser-mcp'],
        tools: ['open_session', 'close_session', 'overview', 'query', 'section', 'elements', 'action']
      },
      '21st-magic': {
        command: 'npx', 
        args: ['@21st-dev/magic'],
        tools: ['create_ui', 'build_component', 'generate_code']
      }
    };
  }

  async testServer(serverName) {
    const server = this.servers[serverName];
    console.log(`\n🧪 Тестирование ${serverName}...`);
    
    return new Promise((resolve) => {
      const child = spawn(server.command, server.args, {
        stdio: ['pipe', 'pipe', 'pipe'],
        cwd: process.cwd()
      });

      let output = '';
      let hasStarted = false;

      child.stdout.on('data', (data) => {
        output += data.toString();
        if (output.includes('Server started') || output.includes('Starting server')) {
          hasStarted = true;
          console.log(`✅ ${serverName} запущен успешно`);
          console.log(`📦 Доступные инструменты: ${server.tools.join(', ')}`);
        }
      });

      child.stderr.on('data', (data) => {
        console.log(`⚠️  ${serverName} stderr:`, data.toString().trim());
      });

      // Закрываем через 3 секунды
      setTimeout(() => {
        child.kill('SIGTERM');
        if (hasStarted) {
          console.log(`✅ ${serverName} тест пройден`);
        } else {
          console.log(`❌ ${serverName} не запустился`);
        }
        resolve(hasStarted);
      }, 3000);

      child.on('close', (code) => {
        console.log(`🔚 ${serverName} завершился с кодом: ${code}`);
      });
    });
  }

  async runTests() {
    console.log('🚀 Запуск тестов MCP серверов...\n');
    
    const results = {};
    
    for (const serverName of Object.keys(this.servers)) {
      results[serverName] = await this.testServer(serverName);
    }
    
    console.log('\n📊 Результаты тестов:');
    console.log('='.repeat(40));
    
    for (const [serverName, success] of Object.entries(results)) {
      const status = success ? '✅ УСПЕХ' : '❌ ОШИБКА';
      console.log(`${serverName}: ${status}`);
    }
    
    console.log('\n🎯 Рекомендации:');
    console.log('- Перезапустите Windsurf для активации MCP');
    console.log('- Проверьте конфигурацию ~/.windsurf/mcp_settings.json');
    console.log('- Используйте инструменты через MCP интерфейс в IDE');
  }
}

// Запуск тестов
const tester = new MCPTester();
tester.runTests().catch(console.error);
