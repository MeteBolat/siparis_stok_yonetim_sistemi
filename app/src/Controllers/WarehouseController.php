<?php
final class WarehouseController extends Controller
{
    public function stock(): void
    {
        Auth::roles(['admin','warehouse']);
        $warehouses = WarehouseModel::listWarehouses($this->pdo);

        $selectedWarehouseId = (int)($_GET['warehouse_id'] ?? 0);
        if ($selectedWarehouseId <= 0 && !empty($warehouses)) {
            $selectedWarehouseId = (int)$warehouses[0]['id'];
        }

        $selectedWarehouse = null;
        foreach ($warehouses as $w) {
            if ((int)$w['id'] === $selectedWarehouseId) {
                $selectedWarehouse = $w;
                break;
            }
        }

        $stocks = [];
        $summary = [
            'total_products' => 0,
            'total_on_hand' => 0,
            'total_reserved' => 0,
            'total_stock_value' => 0.0,
        ];

        if ($selectedWarehouse) {
            $stocks = WarehouseModel::stocks($this->pdo, $selectedWarehouseId);
            foreach ($stocks as $row) {
                $summary['total_products']++;
                $summary['total_on_hand'] += (int)$row['quantity_on_hand'];
                $summary['total_reserved'] += (int)$row['reserved_quantity'];
                $summary['total_stock_value'] += (float)$row['stock_value'];
            }
        }


        $this->render('warehouse/stock.php', [
            'title' => 'Depo Stokları',
            'activeNav' => 'warehouse',
            'warehouses' => $warehouses,
            'selectedWarehouseId' => $selectedWarehouseId,
            'selectedWarehouse' => $selectedWarehouse,
            'stocks' => $stocks,
            'summary' => $summary,
        ]);

    }
}
