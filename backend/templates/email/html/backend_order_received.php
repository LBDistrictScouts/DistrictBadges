<?php
/**
 * Backend-created order receipt variant. The common order content remains in
 * one place so both creation paths stay visually and structurally consistent.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 */

$this->set('backendCreated', true);
require __DIR__ . '/order_received.php';
