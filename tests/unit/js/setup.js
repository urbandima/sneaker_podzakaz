/**
 * Setup файл для Jest тестов
 * Настройка глобального окружения и моков
 */

// Мокаем console для чистого вывода тестов
global.console = {
    ...console,
    error: jest.fn(),
    warn: jest.fn(),
    log: jest.fn(),
};

// Мокаем localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Мокаем sessionStorage
const sessionStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
};
global.sessionStorage = sessionStorageMock;

// Мокаем window.location
delete window.location;
window.location = {
    href: 'http://localhost',
    pathname: '/',
    search: '',
    hash: '',
    reload: jest.fn(),
};

// Мокаем window.alert, confirm, prompt
global.alert = jest.fn();
global.confirm = jest.fn(() => true);
global.prompt = jest.fn();

// Очистка моков после каждого теста
afterEach(() => {
    jest.clearAllMocks();
});
