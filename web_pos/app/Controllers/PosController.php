<?php
// app/Controllers/PosController.php

class PosController extends Controller {

    public function index() {
        $orderModel = $this->model('OrderModel');

        $data = [
            'areas'      => $orderModel->getAreasWithTables(),
            'categories' => $orderModel->getCategories(),
            'products'   => $orderModel->getProducts()
        ];

        $this->view('pos/index', $data);
    }
}