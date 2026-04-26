<?php
/**
 * PdfController — Генерация PDF документов
 *
 * B11.1: Накладная Европочта / Белпочта / СДЭК
 * B11.2: Публичная ссылка на PDF
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\backend\modules\checkout\models\Order;

class PdfController extends BaseAdminController
{
    /**
     * Накладная заказа: PDF если доступна библиотека, иначе HTML для печати
     */
    public function actionInvoice($id)
    {
        $order = Order::findOne($id);
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $company = Yii::$app->settings->getCompany() ?? [];

        // If TCPDF is available, generate a real PDF download
        if (class_exists('TCPDF')) {
            $pdf = $this->generateInvoicePdf($order, $company);
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'application/pdf');
            Yii::$app->response->headers->add('Content-Disposition', 'inline; filename="nakladnaya_' . ($order->order_number ?: $order->id) . '.pdf"');
            return $pdf;
        }

        // Fallback: render a printable HTML invoice (no PDF library needed)
        $this->layout = false;
        return $this->render('invoice', [
            'order'   => $order,
            'company' => $company,
        ]);
    }

    /**
     * Публичная ссылка на накладную (по токену)
     */
    public function actionPublic($token)
    {
        // Проверяем токен
        $order = Order::find()
            ->where(['public_token' => $token])
            ->one();

        if (!$order) {
            throw new NotFoundHttpException('Накладная не найдена');
        }

        $company = Yii::$app->settings->getCompany();
        $pdf = $this->generateInvoicePdf($order, $company);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/pdf');

        return $pdf;
    }

    /**
     * HTML версия накладной для печати
     */
    public function actionPrint($id)
    {
        $order = Order::findOne($id);
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $company = Yii::$app->settings->getCompany();

        $this->layout = false;
        return $this->render('invoice', [
            'order' => $order,
            'company' => $company,
        ]);
    }

    /**
     * Генерация PDF через TCPDF (доступен в vendor)
     */
    private function generateInvoicePdf($order, $company)
    {
        // TCPDF должен быть установлен
        if (!class_exists('TCPDF')) {
            // Fallback: генерируем HTML и конвертируем через wkhtmltopdf если доступен
            return $this->generateHtmlInvoice($order, $company);
        }

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('СНИКЕРХЭД');
        $pdf->SetAuthor($company['name'] ?? 'СНИКЕРХЭД');
        $pdf->SetTitle('Накладная №' . $order->order_number);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // Заголовок
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'НАКЛАДНАЯ', 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, '№ ' . $order->order_number, 0, 1, 'C');
        $pdf->Ln(5);

        // Отправитель
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 6, 'Отправитель:', 0, 0);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, $company['name'] ?? 'СНИКЕРХЭД', 0, 1);
        $pdf->Cell(40, 6, '', 0, 0);
        $pdf->Cell(0, 6, $company['address'] ?? '', 0, 1);
        $pdf->Cell(40, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Тел: ' . ($company['phone'] ?? ''), 0, 1);
        $pdf->Ln(3);

        // Получатель
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 6, 'Получатель:', 0, 0);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, $order->client_name, 0, 1);
        $pdf->Cell(40, 6, '', 0, 0);
        $pdf->Cell(0, 6, $order->address, 0, 1);
        $pdf->Cell(40, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Тел: ' . $order->client_phone, 0, 1);
        $pdf->Ln(5);

        // Товары
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('dejavusans', 'B', 9);

        $pdf->Cell(10, 8, '№', 1, 0, 'C', true);
        $pdf->Cell(80, 8, 'Наименование', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Размер', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Кол-во', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Цена', 1, 1, 'R', true);

        $pdf->SetFont('dejavusans', '', 9);
        $total = 0;

        foreach ($order->orderItems as $i => $item) {
            $pdf->Cell(10, 7, $i + 1, 1, 0, 'C');
            $pdf->Cell(80, 7, $item->product_name, 1, 0, 'L');
            $pdf->Cell(20, 7, $item->size ?: '-', 1, 0, 'C');
            $pdf->Cell(20, 7, $item->quantity, 1, 0, 'C');
            $pdf->Cell(40, 7, number_format($item->price, 2, '.', ' ') . ' BYN', 1, 1, 'R');
            $total += $item->price * $item->quantity;
        }

        // Итого
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(130, 8, 'ИТОГО:', 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($total, 2, '.', ' ') . ' BYN', 1, 1, 'R');

        $pdf->Ln(10);

        // Подписи
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 8, 'Отправитель _________________', 0, 0);
        $pdf->Cell(90, 8, 'Получатель _________________', 0, 1);

        return $pdf->Output('S');
    }

    /**
     * HTML fallback для генерации накладной
     */
    private function generateHtmlInvoice($order, $company)
    {
        $html = $this->renderPartial('invoice-html', [
            'order' => $order,
            'company' => $company,
        ]);

        // Если wkhtmltopdf доступен
        $wkhtmltopdf = '/usr/local/bin/wkhtmltopdf';
        if (file_exists($wkhtmltopdf)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tempFile . '.html', $html);

            $cmd = sprintf(
                '%s --page-size A4 --encoding utf-8 %s - 2>/dev/null',
                escapeshellcmd($wkhtmltopdf),
                escapeshellarg($tempFile . '.html')
            );

            $pdf = shell_exec($cmd);
            unlink($tempFile . '.html');

            return $pdf ?: $html;
        }

        return $html;
    }
}
