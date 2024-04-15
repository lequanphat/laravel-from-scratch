<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelOrder;
use App\Http\Requests\CheckoutOrder;
use App\Http\Requests\CreateDetailedOrder;
use App\Http\Requests\CreateOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $data = [
            'page' => 'Orders',
            'orders' =>  Order::query()->paginate(5),
            'customers' => User::where('is_staff', false)->get(),
        ];
        return view('admin.orders.index', $data);
    }

    public function create(CreateOrder $request)
    {
        $isPaid = $request->input('paid') === 'on' ? true : false;
        $order_data = [
            'total_price' => 0,
            'is_paid' => $isPaid,
            'status' => $request->input('status'),
            'receiver_name' => $request->input('receiver_name'),
            'address' => $request->input('address'),
            'phone_number' => $request->input('phone_number'),
            'customer_id' => $request->input('customer_id') == -1 ? null : $request->input('customer_id'),
            'created_by' => Auth::user()->user_id,
        ];
        $order = Order::create($order_data);
        return ['message' => 'Created order successfully!', 'order' => $order];
    }

    public function update(Request $request)
    {

        $order_id = $request->route('order_id');
        $order = order::where('order_id', $order_id)->first();
        if ($order) {
            $isPaid = $request->input('paid') === 'on' ? true : false;
            $order->update([
                'is_paid' => $isPaid,
                'status' => $request->input('status'),
                'receiver_name' => $request->input('receiver_name'),
                'address' => $request->input('address'),
                'phone_number' => $request->input('phone_number'),
                'customer_id' => $request->input('customer_id') == -1 ? null : $request->input('customer_id'),
            ]);
            return ['message' => 'Update order successfully', 'order' => $order];
        } else {
            response()->json(['errors' => ['message' => ['Cannot find this order.']]], 400);
        }
    }

    //search, hàm trả json về cho bên order_api lấy làm việc trong filterOrders
    public function search_orders_ajax()
    {
        //kiếm dữ liệu theo biến search gửi từ ajax qua thông qua đoạn url
        $search = request()->query('search');
        //kiếm trong 1 khoảng tg
        $day_first = request()->query('dayfirst');
        $day_last = request()->query('daylast');

        //query dữ liệu thường
        $orders = Order::query()->where('order_id', 'LIKE', '%' . $search . '%')->paginate(5);
        //nếu 2 ngày được chọn
        if(isset($day_first) && isset($day_last)){
            //do wherebetween nó chỉ lấy từ ngày đầu cho tới ngày trước ngày cuối nên phải cộng
            $day_last_plus_one = Carbon::parse($day_last)->addDay();
            $orders = Order::query()->where('order_id', 'LIKE', '%' . $search . '%')->whereBetween('created_at', [$day_first, $day_last_plus_one])->paginate(5);
        } elseif (isset($day_first) && empty($day_last)) {
            $orders = Order::query()->where('order_id', 'LIKE', '%' . $search . '%')->whereDate('created_at', '>=' , $day_first)->paginate(5);
        } elseif (empty($day_first) && isset($day_last)) {
            $orders = Order::query()->where('order_id', 'LIKE', '%' . $search . '%')->whereDate('created_at', '<=' , $day_last)->paginate(5);
        }

        foreach( $orders as $order ) {
            $order->howmanydaysago = $order->howmanydaysago();
            $order->money = $order->money_type();
        }
        return response()->json(['order_for_ajax' => $orders]);
    }


    public function details(Request $request)
    {
        $order_id = $request->route('order_id');
        $order = Order::where('order_id', $order_id)->with('employee.default_address')->first();
        $detailedOrders = $order->order_details()->with('detailed_product')->paginate(5); // 5 items per page
        $data = [
            'page' => 'Order Details',
            'order' => $order,
            'detailed_orders' => $detailedOrders,
            'detailed_products' => ProductDetail::paginate(4),
        ];
        return view('admin.orders.order_details', $data);
    }



    public function create_detailed_order(CreateDetailedOrder $request)
    {
        $order_id = $request->route('order_id');
        $sku = $request->input('sku');
        $quantities = $request->input('quantities');
        $unit_price = $request->input('unit_price');
        $detailed_order_exist = OrderDetail::where('order_id', $order_id)->where('sku', $sku)->first();
        if ($detailed_order_exist) {
            OrderDetail::where('order_id', $order_id)->where('sku', $sku)->update([
                'quantities' => $detailed_order_exist->quantities + $quantities,
                'unit_price' => $unit_price,
            ]);
            ProductDetail::where('sku', $sku)->decrement('quantities', $request->input('quantities'));
            return ['message' => 'Created order detail successfully!', 'detailed_order' => $detailed_order_exist];
        } else {
            $order_detail = OrderDetail::create([
                'order_id' => $order_id,
                'sku' => $sku,
                'quantities' => $quantities,
                'unit_price' => $unit_price,
            ]);
        }
        ProductDetail::where('sku', $sku)->decrement('quantities', $request->input('quantities'));
        return ['message' => 'Created order detail successfully!', 'detailed_order' => $order_detail];
    }


    public function payment_with_vnpay($order_id, $amount)
    {
        $vnp_TxnRef = $order_id;
        $vnp_Locale = "vn"; // language
        $vnp_BankCode = "NCB";  // bank code
        $vnp_IpAddr = request()->ip();  // ip address of client
        $vnp_TmnCode = env('VNP_TMN_CODE');

        $vnp_Returnurl =  config('app.url') . 'checkout/' . $order_id;
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan GD: " . $vnp_TxnRef,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        return $vnp_Url;
    }
    public function checkout_order(CheckoutOrder $request)
    {
        // validate data

        $checkout = json_decode($request->input('checkout'), true);
        foreach ($checkout as $item) {
            $product = ProductDetail::where('sku', $item['sku'])->first();
            if (!$product) {
                return response()->json(['errors' => ['message' => ['Cannot find this product.']]], 400);
            }
            if ($product->quantities < $item['quantities']) {
                return response()->json(['errors' => ['message' => ['Not enough quantities for this product.']]], 400);
            }
        }

        // create order

        $order = Order::create([
            'total_price' => 0,
            'is_paid' => false,
            'status' => 0,
            'receiver_name' => $request->input('receiver_name'),
            'address' => $request->input('address'),
            'phone_number' => $request->input('phone_number'),
            'note' => $request->input('note'),
            'customer_id' => Auth::user()->user_id,
            'created_by' => null,
        ]);

        // create order detail
        $total_price = 0;
        foreach ($checkout as $item) {
            $order_detail = OrderDetail::create([
                'order_id' => $order->order_id,
                'sku' => $item['sku'],
                'quantities' => $item['quantities'],
                'unit_price' => $item['unit_price'],
            ]);
            ProductDetail::where('sku', $item['sku'])->decrement('quantities', $item['quantities']);

            $total_price += $order_detail->quantities * $order_detail->unit_price;
        }
        // Update the total_price in the order
        $order->update(['total_price' => $total_price]);

        if ($request->input('payment_method') == 'vnpay') {
            $order->update([
                'is_paid' => true,
            ]);
            return OrderController::payment_with_vnpay($order->order_id, $order->total_price);
        } else {
            return config('app.url') . 'checkout/' . $order->order_id;
        }
    }

    public function cancel_order(CancelOrder $request)
    {
        $order_id = $request->route('order_id');
        $order = Order::where('order_id', $order_id)->first();
        if ($order) {
            $order->update([
                'status' => 4,
                'note' => $order->note . ' - Cancelled by customer',
            ]);
            return ['message' => 'Cancel order successfully', 'order' => $order];
        }
        return response()->json(['errors' => ['message' => ['Cannot find this order.']]], 400);
    }
}
