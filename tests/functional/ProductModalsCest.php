<?php

namespace tests\functional;

use tests\FunctionalTester;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\Brand;

/**
 * Функциональные тесты для модальных окон на странице товара
 */
class ProductModalsCest
{
    private $testProduct;
    
    public function _before(FunctionalTester $I)
    {
        // Создаём тестовый товар
        $brand = Brand::findOne(['name' => 'Nike']) ?? new Brand(['name' => 'Nike']);
        if ($brand->isNewRecord) {
            $brand->save();
        }
        
        $this->testProduct = new Product([
            'name' => 'Test Product for Modals',
            'article' => 'TEST-MODAL-001',
            'brand_id' => $brand->id,
            'price' => 100,
            'is_active' => 1,
        ]);
        $this->testProduct->save();
    }
    
    public function _after(FunctionalTester $I)
    {
        // Удаляем тестовый товар
        if ($this->testProduct && !$this->testProduct->isNewRecord) {
            $this->testProduct->delete();
        }
    }
    
    /**
     * Тест открытия модального окна "Купить в 1 клик"
     */
    public function testOpenQuickOrderModal(FunctionalTester $I)
    {
        $I->wantTo('открыть модальное окно быстрого заказа');
        
        // Переходим на страницу товара
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        $I->seeResponseCodeIs(200);
        
        // Проверяем, что модалка скрыта
        $I->dontSeeElement('#quickOrderModal[style*="display: flex"]');
        
        // Кликаем на кнопку "Купить в 1 клик"
        $I->click('button[onclick*="openQuickOrderModal"]');
        
        // Проверяем, что модалка открылась
        $I->waitForElementVisible('#quickOrderModal', 2);
        $I->seeElement('#quickOrderModal[style*="display: flex"]');
        
        // Проверяем наличие формы
        $I->seeElement('#quickOrderForm');
        $I->seeElement('input[name="name"]');
        $I->seeElement('input[name="phone"]');
    }
    
    /**
     * Тест закрытия модального окна "Купить в 1 клик"
     */
    public function testCloseQuickOrderModal(FunctionalTester $I)
    {
        $I->wantTo('закрыть модальное окно быстрого заказа');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Закрываем модалку кнопкой
        $I->click('.modal-close');
        $I->waitForElementNotVisible('#quickOrderModal', 2);
        $I->dontSeeElement('#quickOrderModal[style*="display: flex"]');
    }
    
    /**
     * Тест закрытия модального окна по клику на фон
     */
    public function testCloseQuickOrderModalByBackdrop(FunctionalTester $I)
    {
        $I->wantTo('закрыть модальное окно кликом на фон');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Кликаем на фон модалки
        $I->executeJS('document.getElementById("quickOrderModal").click()');
        $I->wait(1);
        $I->dontSeeElement('#quickOrderModal[style*="display: flex"]');
    }
    
    /**
     * Тест отправки быстрого заказа с валидацией
     */
    public function testSubmitQuickOrderWithValidation(FunctionalTester $I)
    {
        $I->wantTo('отправить быстрый заказ с проверкой валидации');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Пытаемся отправить пустую форму
        $I->click('#quickOrderSubmitBtn');
        
        // Проверяем, что браузер показывает валидацию HTML5
        $I->seeElement('input[name="name"]:invalid');
        $I->seeElement('input[name="phone"]:invalid');
    }
    
    /**
     * Тест открытия модального окна таблицы размеров
     */
    public function testOpenSizeTableModal(FunctionalTester $I)
    {
        $I->wantTo('открыть модальное окно таблицы размеров');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        $I->seeResponseCodeIs(200);
        
        // Проверяем, что модалка скрыта
        $I->dontSeeElement('#sizeTableModal[style*="display: flex"]');
        
        // Открываем модалку через JS (т.к. кнопка может быть условной)
        $I->executeJS('openSizeTableModal()');
        
        // Проверяем, что модалка открылась
        $I->waitForElementVisible('#sizeTableModal', 2);
        $I->seeElement('#sizeTableModal[style*="display: flex"]');
    }
    
    /**
     * Тест закрытия модального окна таблицы размеров
     */
    public function testCloseSizeTableModal(FunctionalTester $I)
    {
        $I->wantTo('закрыть модальное окно таблицы размеров');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openSizeTableModal()');
        $I->waitForElementVisible('#sizeTableModal', 2);
        
        // Закрываем модалку
        $I->executeJS('closeSizeTableModal()');
        $I->wait(1);
        $I->dontSeeElement('#sizeTableModal[style*="display: flex"]');
    }
    
    /**
     * Тест закрытия модальных окон по ESC
     */
    public function testCloseModalsByEscKey(FunctionalTester $I)
    {
        $I->wantTo('закрыть модальные окна клавишей ESC');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку быстрого заказа
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Нажимаем ESC
        $I->pressKey('#quickOrderModal', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
        $I->wait(1);
        $I->dontSeeElement('#quickOrderModal[style*="display: flex"]');
    }
    
    /**
     * Тест наличия всех необходимых функций в глобальной области
     */
    public function testGlobalFunctionsExist(FunctionalTester $I)
    {
        $I->wantTo('проверить наличие глобальных функций модальных окон');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Проверяем наличие функций в window
        $I->executeJS('return typeof window.openQuickOrderModal === "function"');
        $I->executeJS('return typeof window.closeQuickOrderModal === "function"');
        $I->executeJS('return typeof window.submitQuickOrder === "function"');
        $I->executeJS('return typeof window.openSizeTableModal === "function"');
        $I->executeJS('return typeof window.closeSizeTableModal === "function"');
        $I->executeJS('return typeof window.selectSizeFromTable === "function"');
    }
    
    /**
     * Тест автофокуса на первое поле при открытии модалки
     */
    public function testAutoFocusOnModalOpen(FunctionalTester $I)
    {
        $I->wantTo('проверить автофокус на первое поле при открытии модалки');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        $I->wait(1); // Ждём автофокус
        
        // Проверяем, что фокус на первом поле
        $activeElement = $I->executeJS('return document.activeElement.tagName');
        $I->assertEquals('INPUT', $activeElement);
    }
    
    /**
     * Тест сброса формы при закрытии модалки
     */
    public function testFormResetOnModalClose(FunctionalTester $I)
    {
        $I->wantTo('проверить сброс формы при закрытии модалки');
        
        $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
        
        // Открываем модалку
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Заполняем поля
        $I->fillField('input[name="name"]', 'Test User');
        $I->fillField('input[name="phone"]', '+375291234567');
        
        // Закрываем модалку
        $I->executeJS('closeQuickOrderModal()');
        $I->wait(1);
        
        // Открываем снова
        $I->executeJS('openQuickOrderModal()');
        $I->waitForElementVisible('#quickOrderModal', 2);
        
        // Проверяем, что поля пустые
        $nameValue = $I->grabValueFrom('input[name="name"]');
        $phoneValue = $I->grabValueFrom('input[name="phone"]');
        
        $I->assertEquals('', $nameValue);
        $I->assertEquals('', $phoneValue);
    }
}
