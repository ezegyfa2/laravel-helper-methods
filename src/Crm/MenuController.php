<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductType;

class MenuController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = ProductType::paginate();
        return view('webshop.productList', compact('products'));
    }

    public function showMenu()
    {
        $user = \Auth::user();
        $tableNames = getDatabaseTableNames();
        unset($tableNames[2]);
        unset($tableNames[10]);
        unset($tableNames[21]);
        unset($tableNames[23]);
        return view('menu', compact('user', 'tableNames'));
    }

    public function showTermsAndConditions()
    {
        return view('termsAndConditions');
    }

    public function showPrivateDataProtectionDescription()
    {
        return view('privateDataProtectionDescription');
    }
}
