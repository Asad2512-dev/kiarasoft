<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\SignupRequest;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\ReturnService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reproducible Pakistani grocery demo data. Uses a fixed random seed and the
 * real InventoryService / CheckoutService / ReturnService so every stock
 * movement, invoice and refund is generated exactly as in production use.
 */
class DemoSeeder extends Seeder
{
    public const CATALOG = [
        'Rice & Flour' => [
            ['Basmati Rice Super Kernel 5kg', 'RICE-SK-5', 'pack', 2150], ['Sella Rice 1kg (loose)', 'RICE-SEL-L', 'kg', 390],
            ['Broken Rice (loose)', 'RICE-BRK-L', 'kg', 230], ['Chakki Atta 10kg', 'ATTA-CH-10', 'pack', 1450],
            ['Fine Atta 5kg', 'ATTA-FN-5', 'pack', 760], ['Maida (loose)', 'MAIDA-L', 'kg', 150], ['Besan 1kg', 'BESAN-1', 'pack', 360],
        ],
        'Sugar, Salt & Basics' => [
            ['Sugar (loose)', 'SUGAR-L', 'kg', 155], ['Iodized Salt 800g', 'SALT-800', 'pack', 60], ['Gur (Jaggery)', 'GUR-L', 'kg', 260],
            ['Brown Sugar 500g', 'SUGAR-BR-500', 'pack', 210],
        ],
        'Tea & Beverages' => [
            ['Black Tea Danedar 900g', 'TEA-DAN-900', 'pack', 1690], ['Black Tea 190g', 'TEA-190', 'pack', 390], ['Green Tea 30 bags', 'TEA-GRN-30', 'pack', 320],
            ['Mineral Water 1.5L', 'WATER-1500', 'bottle', 110], ['Cola 1.5L', 'COLA-1500', 'bottle', 200], ['Mango Juice 1L', 'JUICE-MNG-1', 'pack', 270],
            ['Rooh Afza-style Sharbat 800ml', 'SHARBAT-800', 'bottle', 450],
        ],
        'Cooking Oil & Ghee' => [
            ['Cooking Oil 1L Pouch', 'OIL-1L', 'pack', 545], ['Cooking Oil 5L Bottle', 'OIL-5L', 'bottle', 2650], ['Banaspati Ghee 1kg', 'GHEE-1KG', 'pack', 560],
            ['Canola Oil 3L', 'OIL-CAN-3L', 'bottle', 1720], ['Desi Ghee 500g', 'GHEE-DESI-500', 'pack', 1250],
        ],
        'Dairy' => [
            ['Fresh Milk (loose)', 'MILK-L', 'l', 220], ['UHT Milk 1L', 'MILK-UHT-1', 'pack', 290], ['Yogurt (loose)', 'DAHI-L', 'kg', 240],
            ['Butter 200g', 'BUTTER-200', 'pack', 460], ['Eggs (dozen)', 'EGGS-12', 'dozen', 330], ['Cheese Slices 200g', 'CHEESE-200', 'pack', 640],
        ],
        'Pulses & Lentils' => [
            ['Daal Chana (loose)', 'DAAL-CHN-L', 'kg', 300], ['Daal Masoor (loose)', 'DAAL-MSR-L', 'kg', 320], ['Daal Mash (loose)', 'DAAL-MSH-L', 'kg', 560],
            ['Daal Moong (loose)', 'DAAL-MNG-L', 'kg', 380], ['White Chickpeas (loose)', 'CHANA-WHT-L', 'kg', 420], ['Red Kidney Beans (loose)', 'LOBIA-L', 'kg', 480],
        ],
        'Spices & Masala' => [
            ['Red Chilli Powder 200g', 'SPC-CHL-200', 'pack', 230], ['Turmeric Powder 200g', 'SPC-HLD-200', 'pack', 190], ['Coriander Powder 200g', 'SPC-DHN-200', 'pack', 170],
            ['Garam Masala 100g', 'SPC-GRM-100', 'pack', 210], ['Biryani Masala 50g', 'SPC-BRY-50', 'pack', 120], ['Cumin Seeds (loose)', 'SPC-ZRA-L', 'g', 2.40],
        ],
        'Biscuits & Snacks' => [
            ['Glucose Biscuits Family Pack', 'BSC-GLU-FP', 'pack', 120], ['Cream Biscuits Ticky Pack', 'BSC-CRM-TP', 'pack', 30], ['Salted Crackers Half Roll', 'BSC-SLT-HR', 'pack', 60],
            ['Potato Chips 70g', 'SNK-CHP-70', 'pack', 100], ['Nimko Mix 250g', 'SNK-NMK-250', 'pack', 220], ['Rusk 300g', 'BSC-RSK-300', 'pack', 190],
        ],
        'Cleaning & Household' => [
            ['Washing Powder 1kg', 'CLN-WSH-1', 'pack', 520], ['Dishwash Bar 3-pack', 'CLN-DSH-3', 'pack', 210], ['Floor Cleaner 500ml', 'CLN-FLR-500', 'bottle', 330],
            ['Bleach 1L', 'CLN-BLC-1', 'bottle', 260], ['Toilet Cleaner 500ml', 'CLN-TLT-500', 'bottle', 380], ['Matchbox (10 pack)', 'HH-MTCH-10', 'pack', 60],
        ],
        'Personal Care' => [
            ['Bath Soap 115g', 'PC-SOAP-115', 'pcs', 150], ['Shampoo 180ml', 'PC-SHMP-180', 'bottle', 450], ['Toothpaste 100g', 'PC-TPST-100', 'pcs', 260],
            ['Coconut Hair Oil 200ml', 'PC-HOIL-200', 'bottle', 340],
        ],
    ];

    public function run(): void
    {
        mt_srand(20261109);
        $password = config('kirasoft.demo_password') ?: Str::password(12, symbols: false);
        $inventory = app(InventoryService::class);

        // ---------------- Accounts ----------------
        $super = User::create(['name' => 'Platform Admin (Demo)', 'email' => 'admin@kirasoft.test', 'role' => Role::SuperAdmin, 'password' => $password, 'is_active' => true]);

        $shop = Shop::create(['name' => 'Al-Noor Kiryana Store (Demo)', 'slug' => 'al-noor-kiryana-demo', 'owner_name' => 'Imran Qureshi',
            'email' => 'owner@kirasoft.test', 'phone' => '0300-1234567', 'address' => 'Main Double Road', 'city' => 'Islamabad', 'status' => 'active']);
        $shop->settings()->create(['receipt_header' => 'Al-Noor Kiryana Store', 'receipt_footer' => 'Shukriya! Please visit again. Returns within 7 days with receipt.',
            'invoice_prefix' => 'INV', 'tax_enabled' => false, 'tax_rate' => '0', 'default_low_stock_threshold' => '10', 'max_discount_percent' => '20']);

        $g11 = Branch::create(['shop_id' => $shop->id, 'name' => 'G-11 Markaz', 'code' => 'G11', 'address' => 'Shop 14, G-11 Markaz, Islamabad', 'phone' => '051-2345678']);
        $sdr = Branch::create(['shop_id' => $shop->id, 'name' => 'Saddar Rawalpindi', 'code' => 'SDR', 'address' => 'Bank Road, Saddar, Rawalpindi', 'phone' => '051-5556677']);
        $btn = Branch::create(['shop_id' => $shop->id, 'name' => 'Bahria Town Phase 4', 'code' => 'BTN', 'address' => 'Civic Center, Bahria Town', 'phone' => '051-5733000']);

        $owner = User::create(['shop_id' => $shop->id, 'name' => 'Imran Qureshi', 'email' => 'owner@kirasoft.test', 'phone' => '0300-1234567', 'role' => Role::ShopAdmin, 'password' => $password]);
        $manager = User::create(['shop_id' => $shop->id, 'name' => 'Ayesha Siddiqui', 'email' => 'manager@kirasoft.test', 'phone' => '0321-7654321', 'role' => Role::BranchManager, 'password' => $password]);
        $cashier = User::create(['shop_id' => $shop->id, 'name' => 'Bilal Ahmed', 'email' => 'cashier@kirasoft.test', 'phone' => '0333-1112233', 'role' => Role::Cashier, 'password' => $password]);
        $cashier2 = User::create(['shop_id' => $shop->id, 'name' => 'Usman Tariq', 'email' => 'cashier2@kirasoft.test', 'phone' => '0345-9988776', 'role' => Role::Cashier, 'password' => $password]);
        $cashier3 = User::create(['shop_id' => $shop->id, 'name' => 'Sana Javed', 'email' => 'cashier3@kirasoft.test', 'phone' => '0312-4455667', 'role' => Role::Cashier, 'password' => $password]);
        $owner->branches()->attach([$g11->id, $sdr->id, $btn->id]);
        $manager->branches()->attach([$g11->id, $sdr->id]);
        $cashier->branches()->attach($g11->id);
        $cashier2->branches()->attach($sdr->id);
        $cashier3->branches()->attach($btn->id);
        $g11->update(['manager_id' => $manager->id]);
        $sdr->update(['manager_id' => $manager->id]);

        // ---------------- Catalog & opening stock (30 days ago) ----------------
        $realNow = Carbon::now();
        $today = $realNow->copy()->startOfDay();
        $start = $today->copy()->subDays(30)->setTime(8, 0);
        Carbon::setTestNow($start);
        $products = [];
        foreach (self::CATALOG as $catName => $items) {
            $cat = Category::create(['shop_id' => $shop->id, 'name' => $catName]);
            foreach ($items as [$name, $sku, $unit, $price]) {
                $p = Product::create([
                    'shop_id' => $shop->id, 'category_id' => $cat->id, 'name' => $name, 'sku' => $sku, 'unit' => $unit,
                    'price' => number_format($price, 2, '.', ''), 'cost_price' => number_format($price * 0.86, 2, '.', ''),
                    'low_stock_threshold' => $unit === 'g' ? '500' : ($price > 1000 ? '5' : null),
                    'barcode' => '899'.str_pad((string) (count($products) + 1001), 10, '0', STR_PAD_LEFT),
                ]);
                $products[] = $p;
                foreach ([$g11, $sdr, $btn] as $b) {
                    $qty = match ($unit) {
                        'g' => mt_rand(4000, 8000), 'kg', 'l' => mt_rand(80, 200), default => mt_rand(40, 120)
                    };
                    $inventory->adjust($owner, $b, $p, MovementType::Opening, (string) $qty, 'Opening stock');
                }
            }
        }
        // a second, smaller shop to demonstrate multi-tenant isolation
        $other = Shop::create(['name' => 'Karachi Fresh Mart (Demo)', 'slug' => 'karachi-fresh-mart-demo', 'owner_name' => 'Farah Khan',
            'email' => 'farah@freshmart.test', 'phone' => '021-3456789', 'city' => 'Karachi', 'status' => 'active']);
        $other->settings()->create(['receipt_header' => 'Karachi Fresh Mart']);
        $ob = Branch::create(['shop_id' => $other->id, 'name' => 'Clifton', 'code' => 'CLF']);
        $oa = User::create(['shop_id' => $other->id, 'name' => 'Farah Khan', 'email' => 'farah@freshmart.test', 'role' => Role::ShopAdmin, 'password' => $password]);
        $oa->branches()->attach($ob->id);
        $oc = Category::create(['shop_id' => $other->id, 'name' => 'Groceries']);
        foreach ([['Basmati Rice 1kg', 'FM-RICE-1', 'pack', 480], ['Sugar 1kg', 'FM-SUG-1', 'pack', 165], ['Tea 450g', 'FM-TEA-450', 'pack', 890]] as [$n, $s, $u, $pr]) {
            $p = Product::create(['shop_id' => $other->id, 'category_id' => $oc->id, 'name' => $n, 'sku' => $s, 'unit' => $u, 'price' => $pr]);
            $inventory->adjust($oa, $ob, $p, MovementType::Opening, '50', 'Opening stock');
        }

        // ---------------- 30 days of sales ----------------
        $checkout = app(CheckoutService::class);
        $returns = app(ReturnService::class);
        $sellers = [[$g11, $cashier], [$g11, $manager], [$sdr, $cashier2], [$btn, $cashier3]];
        $popular = array_slice($products, 0, 30);
        $made = [];
        for ($d = 29; $d >= 0; $d--) {
            $day = $today->copy()->subDays($d);
            foreach ($sellers as [$branch, $seller]) {
                $n = mt_rand(3, $branch->id === $g11->id ? 9 : 6) + ($day->isWeekend() ? 2 : 0);
                for ($i = 0; $i < $n; $i++) {
                    Carbon::setTestNow($day->copy()->setTime(mt_rand(9, 21), mt_rand(0, 59), mt_rand(0, 59)));
                    if (Carbon::now()->greaterThan($realNow)) {
                        Carbon::setTestNow($today->copy()->setTime(8, mt_rand(0, 59)));
                    }
                    $items = [];
                    $pool = mt_rand(0, 3) ? $popular : $products;
                    foreach ((array) array_rand($pool, mt_rand(1, 6)) as $k) {
                        $p = $pool[$k];
                        $qty = match ($p->unit->value) {
                            'kg', 'l' => [0.5, 1, 1.5, 2, 2.5, 5][mt_rand(0, 5)], 'g' => [50, 100, 250][mt_rand(0, 2)], default => mt_rand(1, 3)
                        };
                        $items[] = ['product_id' => $p->id, 'quantity' => (string) $qty];
                    }
                    $preview = 0;
                    foreach ($items as $it) {
                        $pp = collect($products)->firstWhere('id', $it['product_id']);
                        $preview += Money::lineTotal(Money::qtyToMilli($it['quantity']), Money::toMinor($pp->price));
                    }
                    $discount = mt_rand(0, 9) === 0 ? ['discount_type' => 'percent', 'discount_value' => '5'] : (mt_rand(0, 12) === 0 && $preview > 2000 ? ['discount_type' => 'fixed', 'discount_value' => '50'] : []);
                    $tender = (int) (ceil($preview / 100 / 100) * 100) + (mt_rand(0, 2) ? 0 : 500);
                    try {
                        $made[] = $checkout->checkout($seller, $branch, $items, $discount + [
                            'idempotency_key' => (string) Str::uuid(), 'amount_tendered' => (string) max($tender, 1),
                            'customer_name' => mt_rand(0, 5) === 0 ? ['Ali Raza', 'Hina Malik', 'Zeeshan', 'Mrs. Kazmi', 'Ahmed Bhai'][mt_rand(0, 4)] : null,
                        ]);
                    } catch (BusinessRuleException) {
                        // skip a basket that would oversell (realistic: item ran out)
                    }
                }
            }
            // a restock delivery every 6 days
            if ($d % 6 === 0 && $d > 0) {
                Carbon::setTestNow($day->copy()->setTime(8, 30));
                foreach ([$g11, $sdr, $btn] as $b) {
                    foreach (array_slice($popular, mt_rand(0, 10), 8) as $p) {
                        $inventory->adjust($owner, $b, $p, MovementType::StockIn, (string) ($p->unit->value === 'g' ? 2000 : mt_rand(20, 50)), 'Supplier delivery');
                    }
                }
            }
        }

        // ---------------- Returns (partial & full) ----------------
        foreach ([5, 23, 61, 97, 140] as $idx) {
            $sale = $made[$idx] ?? null;
            if (! $sale) {
                continue;
            }
            Carbon::setTestNow($sale->sold_at->copy()->addHours(2)->min($realNow));
            $item = $sale->items()->first();
            $qty = $idx === 61 ? $item->quantity : Money::qty(Money::qtyFromMilli(max(1, intdiv(Money::qtyToMilli($item->quantity), 2000)) * 1000 > Money::qtyToMilli($item->quantity) ? Money::qtyToMilli($item->quantity) : max(1000, intdiv(Money::qtyToMilli($item->quantity), 2000) * 1000)));
            if (! in_array($item->unit, ['kg', 'g', 'l', 'ml'], true) && Money::qtyToMilli($qty) % 1000) {
                $qty = '1';
            }
            $user = $sale->branch_id === $btn->id ? $owner : $manager;
            $returns->process($user, $sale, [$item->id => $qty], ['Damaged packaging', 'Expired product', 'Wrong item billed', 'Customer changed mind'][$idx % 4], $idx !== 97);
        }

        // ---------------- Low / out of stock examples ----------------
        Carbon::setTestNow($realNow->greaterThan($today->copy()->setTime(7, 45)) ? $today->copy()->setTime(7, 45) : $realNow);
        $lowSet = [[$g11, 'OIL-5L', '3'], [$g11, 'EGGS-12', '4'], [$sdr, 'TEA-DAN-900', '2'], [$btn, 'MILK-UHT-1', '6'], [$g11, 'SNK-CHP-70', '0'], [$sdr, 'CLN-WSH-1', '0'], [$btn, 'GHEE-DESI-500', '1']];
        foreach ($lowSet as [$b, $sku, $target]) {
            $p = collect($products)->firstWhere('sku', $sku);
            $inv = Inventory::where('branch_id', $b->id)->where('product_id', $p->id)->first();
            if (Money::qtyToMilli($inv->quantity) !== Money::qtyToMilli($target)) {
                $inventory->adjust($manager->canAccessBranch($b) ? $manager : $owner, $b, $p, MovementType::Adjustment, $target, 'Physical count correction');
            }
        }

        Carbon::setTestNow();
        SignupRequest::create(['shop_name' => 'Lahore Super Store (Demo request)', 'owner_name' => 'Kamran Butt', 'email' => 'kamran@lahorestore.test',
            'phone' => '0301-2223344', 'city' => 'Lahore', 'message' => 'We have 2 branches in Johar Town and want to start next month.']);
        SignupRequest::create(['shop_name' => 'Peshawar Grocers (Demo request)', 'owner_name' => 'Gul Khan', 'email' => 'gul@peshawargrocers.test',
            'phone' => '0091-5556677', 'city' => 'Peshawar']);

        $this->command?->newLine();
        $this->command?->info('KiraSoft DEMO data seeded: '.count($products).' products, '.Sale::count().' sales.');
        $this->command?->table(['Role', 'E-mail'], [
            ['Super Admin', 'admin@kirasoft.test'], ['Shop Admin', 'owner@kirasoft.test'], ['Branch Manager', 'manager@kirasoft.test'],
            ['Cashier (G-11)', 'cashier@kirasoft.test'], ['Cashier (Saddar)', 'cashier2@kirasoft.test'], ['Cashier (Bahria)', 'cashier3@kirasoft.test'],
        ]);
        $this->command?->warn(config('kirasoft.demo_password')
            ? 'Password for all demo accounts: value of KIRASOFT_DEMO_PASSWORD in .env'
            : "Generated password for all demo accounts: {$password}  (set KIRASOFT_DEMO_PASSWORD in .env to choose your own)");
    }
}
