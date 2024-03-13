<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CategoryController extends Controller
{
    //

    public function category_ui()
    {

        $data = [
            'page' => 'Categories',
            'categories' => Category::all()
        ];
        return view('admin.categories.category', $data);
    }

    public function category_insert(Request $request)
    {
//        $categoryData = [
//            'category_id' => $request->input('category_id'),
//            'name' => $request->input('name'),
//            'description' => $request->input('description'),
//            'index' => $request->input('index'),
//            'parent' => $request->input('parent_id'),
//
//        ];
//        $category = Category::create($categoryData);
//
//        return ['message' => 'Created Category successfully!', 'user' => $category];


        $category = new Category();
        $category->category_id = $request->input('category_id');
        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->index = $request->input('index');
        $category->parent = $request->input('parent_id');
        $category->save();

        return redirect('/admin/categories');
    }

    public function category_delete()

    {

    }

    public function category_update()
    {

    }
}