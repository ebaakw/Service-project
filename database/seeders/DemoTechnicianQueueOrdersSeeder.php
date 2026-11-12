<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Services\Orders\FinalInvoiceService;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds realistic queue + priced/completed demo orders for technicians.
 *
 * Prerequisite: DemoCompanyTechnicianCustomerSeeder
 *
 *   php artisan db:seed --class=DemoTechnicianQueueOrdersSeeder --force
 */
class DemoTechnicianQueueOrdersSeeder extends Seeder
{
    public const ORDER_NUMBER_PREFIX = 'ORD-QUEUE-DEMO-';

    public function run(): void
    {
        $company = Company::query()->where('slug', 'demo-services-co')->first();
        if ($company === null) {
            throw new RuntimeException(
                'Demo company demo-services-co not found. Run DemoCompanyTechnicianCustomerSeeder first.'
            );
        }

        $category = ServiceCategory::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first()
            ?? ServiceCategory::query()->where('is_active', true)->orderBy('id')->first();

        if ($category === null) {
            throw new RuntimeException('No active service category found. Seed the service catalog first.');
        }

        $priceItems = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($priceItems->count() < 3) {
            throw new RuntimeException('Demo price list items missing. Run DemoCompanyTechnicianCustomerSeeder first.');
        }

        $technicians = $this->resolveTechnicians();
        $customers = $this->ensureQueueCustomers();

        Order::query()
            ->where('order_number', 'like', self::ORDER_NUMBER_PREFIX.'%')
            ->forceDelete();

        $queue = app(TechnicianQueueService::class);
        $orderState = app(OrderStateService::class);
        $invoiceService = app(FinalInvoiceService::class);

        $scenarios = $this->scenarios(
            $technicians,
            $customers,
            $company->getKey(),
            $category->getKey(),
            $priceItems
        );

        foreach ($scenarios as $row) {
            $order = Order::query()->create($row['attributes']);
            $orderState->recordInitialCreation($order, null);

            foreach ($row['items'] ?? [] as $item) {
                $order->items()->create($item);
            }

            if (! empty($row['items'])) {
                $invoice = $invoiceService->syncFromOrderItems($order->fresh());

                if (($row['invoice_status'] ?? null) instanceof InvoiceStatus) {
                    $invoice->forceFill([
                        'status' => $row['invoice_status'],
                        'sent_at' => $row['invoice_status'] === InvoiceStatus::Sent
                            || $row['invoice_status'] === InvoiceStatus::Paid
                            ? now()->subHour()
                            : null,
                        'paid_at' => $row['invoice_status'] === InvoiceStatus::Paid
                            ? now()->subMinutes(30)
                            : null,
                    ])->save();
                }
            }

            if (in_array($order->status, $queue->activeQueueStatuses(), true)) {
                $queue->enqueueAssignedOrder($order->fresh());
                if (isset($row['attributes']['queue_position'])) {
                    $order->forceFill([
                        'queue_position' => $row['attributes']['queue_position'],
                        'estimated_duration_minutes' => $row['attributes']['estimated_duration_minutes']
                            ?? $order->estimated_duration_minutes,
                    ])->save();
                }
            } else {
                $queue->clearQueueFields($order->fresh());
            }
        }

        foreach (['technician-demo@example.test', 'technician-demo-2@example.test'] as $email) {
            $tech = $technicians[$email] ?? null;
            if ($tech === null) {
                continue;
            }

            $hasActiveWork = Order::query()
                ->where('technician_id', $tech->getKey())
                ->whereIn('status', [
                    OrderStatus::Accepted->value,
                    OrderStatus::TechnicianOnWay->value,
                    OrderStatus::Arrived->value,
                    OrderStatus::WaitingCustomerPayment->value,
                    OrderStatus::Started->value,
                ])
                ->exists();

            $tech->forceFill([
                'availability_status' => $hasActiveWork
                    ? TechnicianAvailabilityStatus::Busy
                    : TechnicianAvailabilityStatus::Available,
                'is_accepting_orders' => true,
                'estimated_job_duration_minutes' => $tech->estimated_job_duration_minutes ?: 60,
            ])->save();
        }

        $this->printSummary($queue, $technicians);
    }

    /**
     * @param  array<string, Technician>  $technicians
     */
    private function printSummary(TechnicianQueueService $queue, array $technicians): void
    {
        $this->command?->info('Demo technician queue + priced orders seeded ('.self::ORDER_NUMBER_PREFIX.'*).');
        $this->command?->newLine();
        $this->command?->info('Login credentials:');
        $this->command?->line('  Customer : customer-demo@example.test / password');
        $this->command?->line('  Technician (Ahmed): technician-demo@example.test / password');
        $this->command?->line('  Technician (Sara) : technician-demo-2@example.test / password');
        $this->command?->line('  Technician (Yousef): technician-demo-3@example.test / password');
        $this->command?->newLine();

        foreach ($technicians as $email => $technician) {
            $snapshot = $queue->queueSnapshot($technician->fresh());
            $this->command?->line(sprintf(
                '  %s → queue=%d wait=%s',
                $email,
                $snapshot['queued_orders_count'],
                $snapshot['estimated_wait_label']
            ));
        }
    }

    /**
     * @return array<string, Technician>
     */
    private function resolveTechnicians(): array
    {
        $emails = [
            'technician-demo@example.test',
            'technician-demo-2@example.test',
            'technician-demo-3@example.test',
        ];

        $map = [];
        foreach ($emails as $email) {
            $user = User::query()->where('email', $email)->where('type', UserType::Technician)->first();
            $technician = $user?->technician;
            if ($technician === null) {
                throw new RuntimeException("Technician {$email} not found. Run DemoCompanyTechnicianCustomerSeeder first.");
            }
            $map[$email] = $technician;
        }

        return $map;
    }

    /**
     * @return list<Customer>
     */
    private function ensureQueueCustomers(): array
    {
        $defs = [
            ['email' => 'queue-customer-1@example.test', 'name' => 'Queue Customer One', 'name_ar' => 'عميل الطابور ١'],
            ['email' => 'queue-customer-2@example.test', 'name' => 'Queue Customer Two', 'name_ar' => 'عميل الطابور ٢'],
            ['email' => 'queue-customer-3@example.test', 'name' => 'Queue Customer Three', 'name_ar' => 'عميل الطابور ٣'],
            ['email' => 'queue-customer-4@example.test', 'name' => 'Queue Customer Four', 'name_ar' => 'عميل الطابور ٤'],
        ];

        $customers = [];
        foreach ($defs as $i => $def) {
            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'ulid' => (string) Str::ulid(),
                    'name' => $def['name'],
                    'name_ar' => $def['name_ar'],
                    'name_en' => $def['name'],
                    'password' => Hash::make('password'),
                    'type' => UserType::Customer,
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ]
            );

            $customer = Customer::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                []
            );

            $address = CustomerAddress::query()->updateOrCreate(
                [
                    'customer_id' => $customer->getKey(),
                    'label' => 'Home',
                ],
                [
                    'contact_name' => $def['name'],
                    'contact_phone' => '+9655000000'.($i + 1),
                    'full_address' => 'Queue demo address '.($i + 1).', Kuwait City',
                    'lat' => 29.375859 + ($i * 0.002),
                    'lng' => 47.977405 + ($i * 0.002),
                    'is_default' => true,
                ]
            );

            if ($customer->default_address_id === null) {
                $customer->forceFill(['default_address_id' => $address->getKey()])->save();
            }

            $customers[] = $customer->fresh(['addresses']);
        }

        return $customers;
    }

    /**
     * @param  array<string, Technician>  $technicians
     * @param  list<Customer>  $customers
     * @param  Collection<int, CompanyPriceItem>  $priceItems
     * @return list<array{attributes: array<string, mixed>, items?: list<array<string, mixed>>, invoice_status?: InvoiceStatus}>
     */
    private function scenarios(
        array $technicians,
        array $customers,
        int $companyId,
        int $categoryId,
        $priceItems
    ): array {
        $ahmed = $technicians['technician-demo@example.test'];
        $sara = $technicians['technician-demo-2@example.test'];
        $yousef = $technicians['technician-demo-3@example.test'];

        $p0 = $priceItems[0];
        $p1 = $priceItems[1];
        $p2 = $priceItems[2];
        $p3 = $priceItems[3] ?? $priceItems[0];

        $addr = static function (Customer $customer): int {
            return (int) ($customer->default_address_id
                ?? $customer->addresses()->value('id'));
        };

        $lineFromPrice = static function (CompanyPriceItem $item, float $qty = 1, ?float $unitOverride = null, ?string $notes = null): array {
            $unit = $unitOverride ?? (float) $item->price;
            $total = round($qty * $unit, 3);

            return [
                'company_price_item_id' => $item->getKey(),
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'description_ar' => $item->description_ar,
                'description_en' => $item->description_en,
                'quantity' => number_format($qty, 2, '.', ''),
                'unit_price' => number_format($unit, 3, '.', ''),
                'total_price' => number_format($total, 3, '.', ''),
                'warranty_days' => $item->warranty_days,
                'notes' => $notes,
            ];
        };

        $customLine = static function (
            string $nameAr,
            string $nameEn,
            float $qty,
            float $unit,
            ?string $notes = null
        ): array {
            $total = round($qty * $unit, 3);

            return [
                'company_price_item_id' => null,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'description_ar' => null,
                'description_en' => null,
                'quantity' => number_format($qty, 2, '.', ''),
                'unit_price' => number_format($unit, 3, '.', ''),
                'total_price' => number_format($total, 3, '.', ''),
                'warranty_days' => null,
                'notes' => $notes,
            ];
        };

        $base = static function (
            string $orderNumber,
            Technician $technician,
            Customer $customer,
            OrderStatus $status,
            ?int $queuePosition,
            int $durationMinutes,
            array $extra = [],
        ) use ($companyId, $categoryId, $addr): array {
            $now = now();

            $attributes = array_merge([
                'ulid' => (string) Str::ulid(),
                'order_number' => $orderNumber,
                'company_id' => $companyId,
                'customer_id' => $customer->getKey(),
                'customer_address_id' => $addr($customer),
                'technician_id' => $technician->getKey(),
                'service_category_id' => $categoryId,
                'status' => $status,
                'customer_notes' => 'طلب تجريبي لاختبار قائمة الانتظار والتسعير',
                'inspection_fee' => 5.000,
                'inspection_payment_status' => PaymentStatus::Paid,
                'final_payment_status' => PaymentStatus::Pending,
                'inspection_paid_at' => $now->copy()->subHours(5),
                'assigned_at' => $now->copy()->subHours(4),
                'queue_position' => $queuePosition,
                'estimated_duration_minutes' => $durationMinutes,
                'subtotal' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ], $extra);

            return ['attributes' => $attributes];
        };

        $rows = [];

        // --- Ahmed active queue ---
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'001', $ahmed, $customers[0], OrderStatus::Accepted, 1, 60, [
                'accepted_at' => now()->subHour(),
            ]),
            ['items' => []]
        );

        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'002', $ahmed, $customers[1], OrderStatus::Assigned, 2, 60, [
                'assigned_at' => now()->subMinutes(40),
                'accepted_at' => null,
            ]),
            ['items' => []]
        );

        // Ahmed: arrived with price-list items + modified price + custom extra (quote before pay)
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'007', $ahmed, $customers[2], OrderStatus::Arrived, 3, 75, [
                'accepted_at' => now()->subHours(3),
                'technician_on_way_at' => now()->subHours(2),
                'arrived_at' => now()->subMinutes(40),
                'customer_notes' => 'طلب وصول — بنود من قائمة الأسعار + تعديل سعر + مبلغ إضافي',
            ]),
            [
                'items' => [
                    $lineFromPrice($p0, 1, null, 'من قائمة الأسعار'),
                    // Modified: same catalog item but different unit price (technician adjusted)
                    $lineFromPrice($p1, 2, ((float) $p1->price) + 5.000, 'سعر معدّل عن القائمة (+5)'),
                    $customLine('مبلغ إضافي — مواد استهلاكية', 'Extra — consumables', 1, 7.500, 'extra_amount'),
                ],
                'invoice_status' => InvoiceStatus::Draft,
            ]
        );

        // Ahmed: completed job with full priced invoice (history)
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'008', $ahmed, $customers[3], OrderStatus::Completed, null, 90, [
                'accepted_at' => now()->subDays(2),
                'technician_on_way_at' => now()->subDays(2)->addHour(),
                'arrived_at' => now()->subDays(2)->addHours(2),
                'started_at' => now()->subDays(2)->addHours(3),
                'completed_at' => now()->subDay(),
                'quote_accepted_at' => now()->subDays(2)->addHours(2)->addMinutes(30),
                'final_payment_status' => PaymentStatus::Paid,
                'queue_position' => null,
                'customer_notes' => 'طلب مكتمل — يظهر فيه بنود قائمة الأسعار',
            ]),
            [
                'items' => [
                    $lineFromPrice($p0, 1),
                    $lineFromPrice($p2, 1),
                    $lineFromPrice($p3, 1, ((float) $p3->price) - 2.000, 'خصم يدوي على بند القائمة'),
                ],
                'invoice_status' => InvoiceStatus::Paid,
            ]
        );

        // --- Sara queue ---
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'003', $sara, $customers[0], OrderStatus::TechnicianOnWay, 1, 90, [
                'accepted_at' => now()->subHours(2),
                'technician_on_way_at' => now()->subMinutes(25),
                'assigned_at' => now()->subHours(3),
            ]),
            ['items' => []]
        );

        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'004', $sara, $customers[2], OrderStatus::Accepted, 2, 60, [
                'accepted_at' => now()->subMinutes(50),
            ]),
            ['items' => []]
        );

        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'005', $sara, $customers[3], OrderStatus::Assigned, 3, 60, [
                'assigned_at' => now()->subMinutes(20),
                'accepted_at' => null,
            ]),
            ['items' => []]
        );

        // Sara: waiting customer payment — quote sent with mixed catalog + changed qty
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'009', $sara, $customers[1], OrderStatus::WaitingCustomerPayment, 4, 80, [
                'accepted_at' => now()->subHours(5),
                'technician_on_way_at' => now()->subHours(4),
                'arrived_at' => now()->subHours(3),
                'quote_accepted_at' => null,
                'customer_notes' => 'فاتورة مرسلة للعميل — بنود قائمة + كمية معدّلة',
            ]),
            [
                'items' => [
                    $lineFromPrice($p2, 1),
                    $lineFromPrice($p1, 3, null, 'كمية معدّلة (3 بدل 1)'),
                    $customLine('أجور نقل إضافية', 'Extra transport fee', 1, 4.000, 'extra_amount'),
                ],
                'invoice_status' => InvoiceStatus::Sent,
            ]
        );

        // Sara: another completed priced order
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'010', $sara, $customers[0], OrderStatus::Completed, null, 70, [
                'accepted_at' => now()->subDays(5),
                'arrived_at' => now()->subDays(5)->addHours(2),
                'started_at' => now()->subDays(5)->addHours(3),
                'completed_at' => now()->subDays(4),
                'quote_accepted_at' => now()->subDays(5)->addHours(2)->addMinutes(20),
                'final_payment_status' => PaymentStatus::Paid,
                'queue_position' => null,
                'customer_notes' => 'طلب مكتمل لسارة مع بنود أسعار',
            ]),
            [
                'items' => [
                    $lineFromPrice($p0, 1),
                    $lineFromPrice($p1, 1, ((float) $p1->price) + 3.500, 'تعديل سعر فني'),
                ],
                'invoice_status' => InvoiceStatus::Paid,
            ]
        );

        // --- Yousef ---
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'006', $yousef, $customers[1], OrderStatus::Assigned, 1, 45, [
                'assigned_at' => now()->subMinutes(15),
                'accepted_at' => null,
            ]),
            ['items' => []]
        );

        // Yousef completed with single catalog line
        $rows[] = array_merge(
            $base(self::ORDER_NUMBER_PREFIX.'011', $yousef, $customers[2], OrderStatus::Completed, null, 45, [
                'accepted_at' => now()->subDays(3),
                'arrived_at' => now()->subDays(3)->addHour(),
                'started_at' => now()->subDays(3)->addHours(2),
                'completed_at' => now()->subDays(3)->addHours(4),
                'quote_accepted_at' => now()->subDays(3)->addHour()->addMinutes(15),
                'final_payment_status' => PaymentStatus::Paid,
                'queue_position' => null,
            ]),
            [
                'items' => [
                    $lineFromPrice($p2, 2),
                ],
                'invoice_status' => InvoiceStatus::Paid,
            ]
        );

        return $rows;
    }
}
