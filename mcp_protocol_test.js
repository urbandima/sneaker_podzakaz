#!/usr/bin/env node

/**
 * Прямая проверка MCP серверов через JSON-RPC
 */

const { spawn } = require('child_process');

async function testMCPServer(serverName, command, args) {
  return new Promise((resolve) => {
    console.log(`\n🔍 Проверка ${serverName}...`);
    
    const child = spawn(command, args, {
      stdio: ['pipe', 'pipe', 'pipe'],
      cwd: process.cwd()
    });

    let responses = [];
    let isReady = false;

    // Отправляем initialize запрос
    const initRequest = {
      jsonrpc: "2.0",
      id: 1,
      method: "initialize",
      params: {
        protocolVersion: "2024-11-05",
        capabilities: {},
        clientInfo: {
          name: "test-client",
          version: "1.0.0"
        }
      }
    };

    child.stdout.on('data', (data) => {
      const output = data.toString();
      responses.push(output);
      
      // Проверяем на ответы сервера
      if (output.includes('Server started') || output.includes('Starting server')) {
        isReady = true;
        console.log(`✅ ${serverName} готов к работе`);
        
        // Отправляем запрос для получения инструментов
        const toolsRequest = {
          jsonrpc: "2.0", 
          id: 2,
          method: "tools/list"
        };
        
        child.stdin.write(JSON.stringify(toolsRequest) + '\n');
      }
      
      if (output.includes('"result"') && output.includes('tools')) {
        try {
          const response = JSON.parse(output.trim());
          if (response.result && response.result.tools) {
            console.log(`🛠️  Найдено инструментов: ${response.result.tools.length}`);
            response.result.tools.forEach(tool => {
              console.log(`   - ${tool.name}: ${tool.description?.substring(0, 50)}...`);
            });
          }
        } catch (e) {
          // Игнорируем ошибки парсинга
        }
      }
    });

    child.stderr.on('data', (data) => {
      const error = data.toString().trim();
      if (error && !error.includes('Transport error')) {
        console.log(`⚠️  ${serverName}: ${error}`);
      }
    });

    // Закрываем через 5 секунд
    setTimeout(() => {
      child.kill('SIGTERM');
      console.log(`${isReady ? '✅' : '❌'} ${serverName}: ${isReady ? 'УСПЕХ' : 'НЕ УДАЧНО'}`);
      resolve(isReady);
    }, 5000);

    // Отправляем initialize после запуска
    setTimeout(() => {
      child.stdin.write(JSON.stringify(initRequest) + '\n');
    }, 1000);
  });
}

async function runMCPTests() {
  console.log('🚀 Тестирование MCP серверов через протокол...\n');
  
  const servers = [
    {
      name: 'Browser MCP',
      command: 'npx',
      args: ['@sanity-labs/browser-mcp']
    },
    {
      name: '21st Magic', 
      command: 'npx',
      args: ['@21st-dev/magic']
    }
  ];

  const results = [];
  
  for (const server of servers) {
    const success = await testMCPServer(server.name, server.command, server.args);
    results.push({ name: server.name, success });
  }
  
  console.log('\n📊 Итоги тестирования:');
  console.log('='.repeat(50));
  
  results.forEach(({ name, success }) => {
    console.log(`${success ? '✅' : '❌'} ${name}: ${success ? 'РАБОТАЕТ' : 'ПРОБЛЕМА'}`);
  });
  
  console.log('\n💡 Следующие шаги:');
  console.log('1. Перезапустите Windsurf/Cursor/Claude');
  console.log('2. Проверьте MCP конфигурацию');
  console.log('3. Используйте инструменты в IDE');
}

runMCPTests().catch(console.error);
