<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function getCard()
    {
        $product =  Product::with('category')->get();

        if (Auth::user()->role->name == 'User') {
            return view('product.card', ['products'=>$product]);
        } else {
            return view('product.index', ['products'=>$product]);
        }
    }

    public function index(Request $request)
    {
        //index
        $product =  Product::with('category')->get();

        if (Auth::user()->role->name == 'User') {
            return view('product.card', ['product'=>$product]);
        } else {
            return view('product.index', ['product'=>$product]);
        }
        
        // Get status
        $approve = $request->approve;
    
        // Query the sliders based on the selected status
        $query = Product::query();
    
        if ($approve) {
            $query->where('approve', $approve);
        }
    
        // Get the filtered product
        $product = $query->get();
    
        // Get the count of reject product
        $rejectCountP = $this->getRejectCountP();
        
        // Get the count of acc product
        $approveCountC = $this->getApproveCountC();

        // passing data product, status, and reject count to view slider.index
        return view('product.index', compact('product', 'status', 'rejectCountP', 'approveCountC'));
    }

    public function getRejectCountP()
    {
        $rejectCountP = Product::where('approve', '0')->count();
    
        return $rejectCountP;
    }

    public function getApproveCountC()
    {
        $approveCountC = Product::where('approve', '1')->count();
    
        return $approveCountC;
    }

    public function show($id)
    {
        $product = Product::where('id', $id)->with('category')->first();

        $related = Product::where('category_id', $product->category->id)->inRandomOrder()->limit(4)->get();

        if ($product) {
            return view('product.show', compact('product', 'related'));
        } else {
            abort(404);
        }

    }

    public function create()
    {
        $categories = Category::all();

        return view('product.create', compact('categories'));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'category' => 'required',
            'name' => 'required|string|min:3',
            'price' => 'required|integer',
            'sale_price' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        // ubah nama file
        $imageName = time() . '.' . $request->image->extension();

        // simpan file ke folder public/product
        Storage::putFileAs('public/product', $request->image, $imageName);

        $product = Product::create([
            'category_id' => $request->category,
            'name' => $request->name,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'image' => $imageName,
        ]);

        return redirect()->route('product.index')->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        // ambil data product berdasarkan id
        $product = Product::where('id', $id)->with('category')->first();

        // ambil data category sebagai isian di pilihan (select)
        $categories = Category::all();

        // tampilkan view edit dan passing data product
        return view('product.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        // cek jika user mengupload gambar di form
        if ($request->hasFile('image')) {
            // ambil nama file gambar lama dari database
            $old_image = Product::find($id)->image;

            // hapus file gambar lama dari folder slider
            Storage::delete('public/product/'.$old_image);

            // ubah nama file
            $imageName = time() . '.' . $request->image->extension();

            // simpan file ke folder public/product
            Storage::putFileAs('public/product', $request->image, $imageName);

            // update data product
            Product::where('id', $id)->update([
                'category_id' => $request->category,
                'name' => $request->name,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
                'image' => $imageName,
            ]);

        } else {
            // update data product tanpa menyertakan file gambar
            Product::where('id', $id)->update([
                'category_id' => $request->category,
                'name' => $request->name,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
            ]);
        }

        // redirect ke halaman product.index
        return redirect()->route('product.index')->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        // ambil data product berdasarkan id
        $product = Product::find($id);

        // hapus data product
        $product->delete();

        // redirect ke halaman product.index
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
    
    public function approve($id)
    {
        // ambil data product berdasarkan id
        $product = Product::find($id);

        // update data product
        $product->update([
            'approve' => '1',
        ]);

        // redirect ke halaman product.index
        return redirect()->back()->with('success', 'Product approved successfully.');
    }

    public function reject($id)
    {
        // ambil data product berdasarkan id
        $product = Product::find($id);

        // update data product
        $product->update([
            'approve' => '0',
        ]);

        // redirect ke halaman product.index
        return redirect()->back()->with('success', 'Product rejected successfully.');
    }
}