<?php
class IndexController extends ControllerBase
{
    public function indexAction($uri)
    {
        $this->renderView();
    }

    public function bookkeepingAction($uri)
    {
        $openTransactions = Transaction::getDb()->sql('SELECT COUNT(*) FROM ' . Db::name(Transaction::getTableName()) . '
WHERE status NOT IN (' . TransactionStatus::ID_COMPLETED . ', ' . TransactionStatus::ID_CANCELED . ') LIMIT 1', PDO::FETCH_COLUMN);
        $balances = array(
                BookkeepingAccount::find(BookkeepingAccount::ID_PAY_SYSTEM)->data['balance'],
                BookkeepingAccount::find(BookkeepingAccount::ID_CUSTOMERS_BALANCE)->data['balance'],
                BookkeepingAccount::find(BookkeepingAccount::ID_EXECUTORS_BALANCE)->data['balance'],
                BookkeepingAccount::find(BookkeepingAccount::ID_ORDERS_FUND)->data['balance'],
                BookkeepingAccount::find(BookkeepingAccount::ID_PROFIT)->data['balance'],
        );
        $summAsset = $balances[0];
        $summLiabilities = $balances[1] + $balances[2] + $balances[3] + $balances[4];
        $summObligations = $balances[1] + $balances[2] + $balances[3];
        $db = User::getDb();
        $customerBalances = $db->sql('SELECT SUM(balance) FROM ' . Db::name(User::getTableName()) . '
WHERE role=' . UserRole::ID_CUSTOMER . ' LIMIT 1', PDO::FETCH_COLUMN);
        $executorBalances = $db->sql('SELECT SUM(balance) FROM ' . Db::name(User::getTableName()) . '
WHERE role=' . UserRole::ID_EXECUTOR . ' LIMIT 1', PDO::FETCH_COLUMN);
        $db = Order::getDb();
        $newOrdersSumm = $db->sql('SELECT SUM(total_cost) FROM ' . Db::name(Order::getTableName()) . '
WHERE status=' . OrderStatus::ID_NEW . ' LIMIT 1', PDO::FETCH_COLUMN);
        $executedOrdersCommissionSumm = $db->sql('SELECT SUM(commission) FROM ' . Db::name(Order::getTableName()) . '
WHERE status=' . OrderStatus::ID_EXECUTED . ' LIMIT 1', PDO::FETCH_COLUMN);
        $this->renderView(array(
            'openTransactions'             => $openTransactions,
            'balances'                     => $balances,
            'summAsset'                    => $summAsset,
            'summLiabilities'              => $summLiabilities,
            'summObligations'              => $summObligations,
            'customerBalances'             => $customerBalances,
            'executorBalances'             => $executorBalances,
            'newOrdersSumm'                => $newOrdersSumm,
            'executedOrdersCommissionSumm' => $executedOrdersCommissionSumm,
        ));
    }
}
