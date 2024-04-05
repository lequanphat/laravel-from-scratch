@extends('layouts.app')
@section('content')
    @include('components.head-banner')

    <div class="shop-area shop-page-responsive pt-100 pb-100">
        <div class="container">
            <div class="row flex-row-reverse">
                <div class="col-lg-9">
                    <div class="shop-topbar-wrapper mb-40">
                        <div class="shop-topbar-left">
                            <div class="showing-item">
                                <span>
                                    {{ 'Showing ' . $products->currentPage() * 9 - 8 . ' - ' . $products->currentPage() * 9 . ' of ' . $products->total() . ' results' }}
                                </span>
                            </div>
                        </div>
                        <div class="shop-topbar-right">
                            <div class="shop-sorting-area">
                                <select id="sort-product" class="nice-select nice-select-style-1">
                                    <option value="default"
                                        @if ($sorted_by == 'default') @selected(true) @endif>Default
                                        Sorting</option>
                                    <option value="price_asc"
                                        @if ($sorted_by == 'price_asc') @selected(true) @endif>Sort by price
                                        ascending</option>
                                    <option value="price_des"
                                        @if ($sorted_by == 'price_des') @selected(true) @endif>Sort by price
                                        descending</option>
                                    <option value="latest"
                                        @if ($sorted_by == 'latest') @selected(true) @endif>Sort
                                        by latest</option>
                                    <option value="oldest"
                                        @if ($sorted_by == 'oldest') @selected(true) @endif>Sort
                                        by oldest</option>
                                </select>
                            </div>
                            <div class="shop-view-mode nav">
                                <a class="active" href="#shop-1" data-bs-toggle="tab"><i class=" ti-layout-grid3 "></i> </a>
                                <a href="#shop-2" data-bs-toggle="tab" class=""><i class=" ti-view-list-alt "></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="shop-bottom-area">
                        <div class="tab-content jump">
                            <div id="shop-1" class="tab-pane active">
                                <div id="product-list" class="row">
                                    @foreach ($products as $product)
                                        @php
                                            $today = now();

                                            $detailed_product =
                                                $product->detailed_products
                                                    ->sortByDesc(function ($detailed_product) use ($today) {
                                                        return $detailed_product->product_discounts
                                                            ->where('discount.start_date', '<=', $today)
                                                            ->where('discount.end_date', '>=', $today)
                                                            ->sum('discount.percentage');
                                                    })
                                                    ->first() ?? $product->detailed_products->first();

                                            $discount_percentage = $detailed_product->product_discounts
                                                ->where('discount.start_date', '<=', $today)
                                                ->where('discount.end_date', '>=', $today)
                                                ->sum('discount.percentage');

                                            $total_quantities = $product->detailed_products->sum('quantities');
                                        @endphp
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
                                            <div class="product-wrap mb-35" data-aos="fade-up" data-aos-delay="200">
                                                <div class="custom-product-img product-img img-zoom mb-25">
                                                    <a href="/products/{{ $product->product_id }}">
                                                        @php
                                                            $imageUrl = asset('images/product/product-5.png');
                                                            if ($detailed_product->images->first()) {
                                                                $imageUrl = $detailed_product->images->first()->url;
                                                            }
                                                        @endphp
                                                        <img src="{{ $imageUrl }}" alt="" style="height: 275px">
                                                    </a>
                                                    <div class="product-badge badge-top badge-right badge-pink">
                                                        <span>{{ $discount_percentage != 0 ? '-' . $discount_percentage . '%' : '' }}</span>
                                                    </div>
                                                    @if ($total_quantities == 0)
                                                        <div
                                                            class="custom-product-badge product-badge badge-top badge-left badge-pink">
                                                            <span>Sold out</span>
                                                        </div>
                                                    @endif
                                                    <div class="product-action-wrap">
                                                        <a class="product-action-btn-1" title="Wishlist"><i
                                                                class="pe-7s-like"></i></a>
                                                        <button class="product-action-btn-1" title="Quick View"
                                                            data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                            <i class="pe-7s-look"></i>
                                                        </button>
                                                    </div>
                                                    <div class="product-action-2-wrap">
                                                        <button class="product-action-btn-2" title="Add To Cart"><i
                                                                class="pe-7s-cart"></i> Add to cart</button>
                                                    </div>
                                                </div>
                                                <div class="product-content">
                                                    <h3><a>{{ $product->name }}</a>
                                                    </h3>
                                                    <div class="product-price">
                                                        @if ($discount_percentage > 0)
                                                            <span class="old-price">
                                                                {{ number_format($detailed_product->original_price, 0, '.', ',') }}đ
                                                            </span>
                                                        @endif
                                                        <span class="new-price">
                                                            {{ number_format($detailed_product->original_price - ($detailed_product->original_price * $discount_percentage) / 100, 0, '.', ',') }}đ
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                                <div class="pagination-style-1" data-aos="fade-up" data-aos-delay="200">
                                    <ul>

                                        @for ($i = 1; $i <= $products->lastPage(); $i++)
                                            <li>
                                                <a class="pagination-item
                                                    {{ $retVal = $i == $products->currentPage() ? 'active' : '' }}"
                                                    href="">{{ $i }}</a>
                                            </li>
                                        @endfor
                                    </ul>
                                </div>
                            </div>
                            <div id="shop-2" class="tab-pane">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="sidebar-wrapper">
                        <div class="sidebar-widget mb-40" data-aos="fade-up" data-aos-delay="200">
                            <div class="search-wrap-2">
                                <form class="search-2-form" action="#">
                                    <input id="search-input" placeholder="Search products..." type="text"
                                        value="{{ $search }}">
                                    <button class="button-search"><i class=" ti-search "></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="sidebar-widget sidebar-widget-border mb-40 pb-35" data-aos="fade-up"
                            data-aos-delay="200">
                            <div class="sidebar-widget-title mb-30">
                                <h3>Filter By Price</h3>
                            </div>
                            <div class="price-filter">
                                <div id="slider-range"></div>
                                <div class="price-slider-amount">
                                    <div class="label-input">
                                        <label>Price:</label>
                                        <input type="text" id="amount" name="price" placeholder="Add Your Price" />
                                    </div>
                                    <button type="button">Filter</button>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-widget sidebar-widget-border mb-40 pb-35" data-aos="fade-up"
                            data-aos-delay="200">
                            <div class="sidebar-widget-title mb-25">
                                <h3>Product Categories</h3>
                            </div>
                            <div class="sidebar-list-style">
                                <ul>
                                    @foreach ($categories as $category)
                                        <li>
                                            <a><label for="category_{{ $loop->index }}">{{ $category->name }}
                                                </label><span>
                                                    <input id="category_{{ $loop->index }}"
                                                        data-id="{{ $category->category_id }}" class="js-cate-checkbox"
                                                        type="checkbox"
                                                        @if (in_array($category->category_id, $selected_categories)) @checked(true) @endif>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="sidebar-widget sidebar-widget-border mb-40 pb-35" data-aos="fade-up"
                            data-aos-delay="200">
                            <div class="sidebar-widget-title mb-25">
                                <h3>Choose Colour</h3>
                            </div>
                            <div class="sidebar-widget-color sidebar-list-style">
                                <ul>

                                    @foreach ($colors as $color)
                                        <li><a class="black">
                                                <div class="d-flex">
                                                    <div class="color" style="background-color: {{ $color->code }}">
                                                    </div>
                                                    <label for="color_{{ $loop->index }}">{{ $color->name }}</label>
                                                </div>
                                                <span>
                                                    <input id="color_{{ $loop->index }}"
                                                        data-id="{{ $color->color_id }}" class="js-color-checkbox"
                                                        type="checkbox"
                                                        @if (in_array($color->color_id, $selected_colors)) @checked(true) @endif>
                                                </span>
                                            </a></li>
                                    @endforeach


                                </ul>
                            </div>
                        </div>
                        <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="200">
                            <div class="sidebar-widget-title mb-25">
                                <h3>Tags</h3>
                            </div>
                            <div class="sidebar-widget-tag">
                                @foreach ($tags as $tag)
                                    <a>#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Product Modal start -->
    <div class="modal fade quickview-modal-style" id="exampleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <a href="#" class="close" data-bs-dismiss="modal" aria-label="Close"><i
                            class=" ti-close "></i></a>
                </div>
                <div class="modal-body">
                    <div class="row gx-0">
                        <div class="col-lg-5 col-md-5 col-12">
                            <div class="modal-img-wrap">
                                <img src="{{ asset('images/product/quickview.png') }}" alt="">
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-7 col-12">
                            <div class="product-details-content quickview-content">
                                <h2>New Modern Chair</h2>
                                <div class="product-details-price">
                                    <span class="old-price">$25.89 </span>
                                    <span class="new-price">$20.25</span>
                                </div>
                                <div class="product-details-review">
                                    <div class="product-rating">
                                        <i class=" ti-star"></i>
                                        <i class=" ti-star"></i>
                                        <i class=" ti-star"></i>
                                        <i class=" ti-star"></i>
                                        <i class=" ti-star"></i>
                                    </div>
                                    <span>( 1 Customer Review )</span>
                                </div>
                                <div class="product-color product-color-active product-details-color">
                                    <span>Color :</span>
                                    <ul>
                                        <li><a title="Pink" class="pink" href="#">pink</a></li>
                                        <li><a title="Yellow" class="active yellow" href="#">yellow</a></li>
                                        <li><a title="Purple" class="purple" href="#">purple</a></li>
                                    </ul>
                                </div>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ornare tincidunt neque
                                    vel semper. Cras placerat enim sed nisl mattis eleifend.</p>
                                <div class="product-details-action-wrap">
                                    <div class="product-quality">
                                        <input class="cart-plus-minus-box input-text qty text" name="qtybutton"
                                            value="1">
                                    </div>
                                    <div class="single-product-cart btn-hover">
                                        <a href="#">Add to cart</a>
                                    </div>
                                    <div class="single-product-wishlist">
                                        <a title="Wishlist" href="#"><i class="pe-7s-like"></i></a>
                                    </div>
                                    <div class="single-product-compare">
                                        <a title="Compare" href="#"><i class="pe-7s-shuffle"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Product Modal end -->
@endsection
