<?php
require_once 'models/expensesModel.php';
require_once 'models/categoriesModel.php';
require_once 'models/joinExpensesCategoriesModel.php';

class Expenses extends SessionController{
    //private $view;
    //private $model;

    private $user;

    function __construct()
    {
        parent::__construct();
        $this->user = $this->getUserSessionData();
    }

    function render(){
        $this->view->render('expenses/index', [
            'user' => $this->user,
            'dates' => $this->getDateList(),
            'categories' => $this->getCategoryList()
        ]);
    }

    function newExpense(){
        if(!$this->existPOST(['title', 'amount', 'category', 'date'])){
            $this->redirect('dashboard', [
                'error' => ErrorMessages::ERROR_EXPENSES_NEWEXPENSE_EMPTY
            ]);
            return;
        }

        if($this->user == NULL){
            $this->redirect('dashboard', [
                'error' => ErrorMessages::ERROR_EXPENSES_NEWEXPENSE
            ]);
            return;
        }

        $expense = new ExpensesModel();
        $expense->setTitle($this->getPost('title'));
        $expense->setAmount((float)$this->getPost('amount'));
        $expense->setCategoryId($this->getPost('category'));
        $expense->setDate($this->getPost('date'));
        $expense->setUserId($this->user->getId());

        $expense->save();
        $this->redirect('dashboard', [
            'success' => SuccessMessages::SUCCESS_EXPENSES_NEWEXPENSE
        ]);
    }

    function create(){
        $categories = new CategoriesModel();
        $this->view->render('expenses/create', [
            'categories' => $categories->getAll(),
            'user' => $this->user
        ]);
    }

    function getCategoriesId(){
        $joinModel = new JoinExpensesCategoriesModel();
        $categories = $joinModel->getAll($this->user->getId());
        $res = [];

        foreach($categories as $cat){
            array_push($res, $cat->getCategoryId());
        }

        $res = array_values(array_unique($res));

        return $res;
    }

    private function getDateList(){
        $months = [];
        $res = [];
        $joinModel = new JoinExpensesCategoriesModel();
        $expenses = $joinModel->getAll($this->user->getId());

        foreach($expenses as $expense){
            array_push($months, substr($expense->getDate(), 0, 7));
        }

        $months = array_values(array_unique($months));

        foreach($months as $month){
            array_push($res, $month);
        }

        /*
        if(count($months) > 3){
            array_push($res, array_pop($months));
            array_push($res, array_pop($months));
            array_push($res, array_pop($months));
        }
        */
        error_log('Expenses::getDateList ->' . count($res));
        return $res;
    }

    function getCategoryList(){
        $res = [];
        $joinModel = new JoinExpensesCategoriesModel();
        $expenses = $joinModel->getAll($this->user->getId());

        foreach($expenses as $expense){
            array_push($res, $expense->getNameCategory());
        }
        $res = array_values(array_unique($res));

        return $res;
    }

    function getCategoryColorList(){
        $res = [];
        $joinModel = new JoinExpensesCategoriesModel();
        $expenses = $joinModel->getAll($this->user->getId());

        foreach($expenses as $expense){
            array_push($res, $expense->getColor());
        }
        $res = array_unique($res);
        $res = array_values(array_unique($res));

        return $res;
    }

    function getHistoryJSON(){
        header('Content-Type: application/json');
        $res = [];
        $joinModel = new JoinExpensesCategoriesModel();
        $expenses = $joinModel->getAll($this->user->getId());

        foreach($expenses as $expense){
            array_push($res, $expense->toArray());
        }

        echo json_encode($res);
    }

    function getExpensesJSON(){
        header('Content-Type: application/json');

        $res = [];
        $categoryIds = $this->getCategoriesId();
        $categoryNames = $this->getCategoryList();
        $categoryColors = $this->getCategoryColorList();

        array_unshift($categoryNames, 'mes');
        array_unshift($categoryColors, 'categories');

        $months = $this->getDateList();

        for($i = 0; $i < count($months); $i++){
            $item = array($months[$i]);
            for($j = 0; $j < count($categoryIds); $j++){
                $total = $this->getTotalByMonthAndCategory($months[$i], $categoryIds[$j]);
                array_push($item, $total);
            }
            array_push($res, $item);
        }
        array_unshift($res, $categoryNames);
        array_unshift($res, $categoryColors);

        echo json_encode($res);
    }

    private function getTotalByMonthAndCategory($date, $categoryId){
        $iduser = $this->user->getId();
        //$expenses = new ExpensesModel();
        //$total = $expenses->getTotalByMonthAndCategory($date, $categoryId, $iduser);
        $total = $this->model->getTotalByMonthAndCategory($date, $categoryId, $iduser);

        if($total == NULL){
            $total = 0;
        }

        return $total;
    }

    function delete($params){
        if($params == NULL){
            $this->redirect('expenses', [
                'error' => ErrorMessages::ERROR_EXPENSES_DELETE
            ]);
            error_log('Expenses::delete() -> params null');
        }

        $id = $params[0];
        $res = $this->model->delete($id);

        if($res){
            error_log('Expenses::delete() -> eliminado' . $id);
            $this->redirect('expenses', [
                'success' => SuccessMessages::SUCCESS_EXPENSES_DELETE
            ]);
        }
        else{
            error_log('Expenses::delete() -> error');
            $this->redirect('expenses', [
                'error' => ErrorMessages::ERROR_EXPENSES_DELETE
            ]);
        }
    }
}
?>