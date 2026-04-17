<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Review;
use App\Models\Payment;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Customer User
        $customer = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // Create random additional customers
        $users = User::factory(5)->create(['role' => 'customer']);

        // Create Categories and Products
        $categories = [
            'Electronics',
            'Clothing',
            'Home & Garden',
            'Sports & Outdoors',
            'Toys & Games'
        ];

        foreach ($categories as $categoryName) {
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => \Illuminate\Support\Str::slug($categoryName),
            ]);

            Product::factory(10)->create([
                'category_id' => $category->id
            ]);
        }

        // Seed some Carts and Orders for realism
        $products = Product::all();
        $allCustomers = collect([$customer])->merge($users);

        foreach ($allCustomers as $user) {
            // Give them an address
            Address::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                'full_name' => $user->name,
                'phone' => fake()->phoneNumber(),
                'address_line_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->country(),
                'is_default' => true,
            ]);

            // Give them a cart
            $cart = Cart::create(['user_id' => $user->id]);

            // Add items to cart
            $cartItems = $products->random(rand(1, 3));
            foreach ($cartItems as $item) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $item->id,
                    'quantity' => rand(1, 3),
                ]);
            }

            // Give them reviews and wishlists
            $randomProducts = $products->random(rand(2, 5));
            foreach ($randomProducts as $prod) {
                if (rand(0, 1)) {
                    Wishlist::firstOrCreate(['user_id' => $user->id, 'product_id' => $prod->id]);
                }
                if (rand(0, 1)) {
                    Review::create([
                        'user_id' => $user->id,
                        'product_id' => $prod->id,
                        'rating' => rand(3, 5),
                        'comment' => fake()->sentence(),
                        'is_approved' => true,
                    ]);
                }
            }

            // Give them past orders
            if (rand(0, 1)) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
                    'total_amount' => 0, // Will calculate
                    'shipping_address' => fake()->address(),
                    'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
                ]);

                $orderTotal = 0;
                $orderItems = $products->random(rand(1, 5));
                foreach ($orderItems as $item) {
                    $qty = rand(1, 3);
                    $price = $item->price;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->id,
                        'quantity' => $qty,
                        'price' => $price,
                    ]);
                    $orderTotal += ($qty * $price);
                }

                $order->update(['total_amount' => $orderTotal]);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => fake()->randomElement(['stripe', 'paypal', 'cod']),
                    'transaction_id' => fake()->uuid(),
                    'amount' => $orderTotal,
                    'status' => $order->payment_status === 'paid' ? 'completed' : ($order->payment_status === 'failed' ? 'failed' : 'pending'),
                ]);
            }
        }
    }
}
