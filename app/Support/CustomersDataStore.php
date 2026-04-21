<?php

namespace App\Support;

use RuntimeException;

class CustomersDataStore
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/customers_api_data.json');
    }

    public function read(): array
    {
        $this->ensureExists();

        $handle = fopen($this->filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open customers API data file.');
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Unable to acquire shared lock for customers API data.');
            }

            $raw = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        $decoded = json_decode($raw ?: '', true);
        if (!is_array($decoded)) {
            return $this->seedData();
        }

        return $this->stripMeta($this->normalize($decoded));
    }

    public function update(callable $mutator): array
    {
        $this->ensureExists();

        $handle = fopen($this->filePath, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open customers API data file for write.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire write lock for customers API data.');
            }

            $raw = stream_get_contents($handle);
            $decoded = json_decode($raw ?: '', true);
            $data = is_array($decoded) ? $this->normalize($decoded) : $this->seedData();

            $updated = $mutator($data);
            if (!is_array($updated)) {
                $updated = $data;
            }

            $updated = $this->normalize($updated);
            $persistable = $this->stripMeta($updated);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($persistable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $updated;
        } finally {
            fclose($handle);
        }
    }

    public function nextId(array $records): int
    {
        if ($records === []) {
            return 1;
        }

        $ids = array_map(static fn (array $item) => (int) ($item['id'] ?? 0), $records);

        return max($ids) + 1;
    }

    private function ensureExists(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (!file_exists($this->filePath)) {
            file_put_contents(
                $this->filePath,
                json_encode($this->seedData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    private function normalize(array $data): array
    {
        $seed = $this->seedData();

        foreach (array_keys($seed) as $key) {
            if (!array_key_exists($key, $data) || !is_array($data[$key])) {
                $data[$key] = $seed[$key];
            }
        }

        return $data;
    }

    private function stripMeta(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, '_')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    private function seedData(): array
    {
        return [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'Demo Customer',
                    'email' => 'demo@curatedstore.in',
                    'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
                    'phone' => '+91 99999 99999',
                    'addresses' => [
                        [
                            'id' => 1,
                            'label' => 'Home',
                            'line1' => '221B Curated Residency',
                            'line2' => 'MG Road',
                            'city' => 'Bengaluru',
                            'state' => 'Karnataka',
                            'postal_code' => '560001',
                            'country' => 'India',
                            'is_default' => true,
                        ],
                    ],
                    'preferences' => [
                        'currency' => 'INR',
                        'language' => 'en',
                        'email_notifications' => true,
                        'sms_notifications' => false,
                    ],
                    'wishlist' => [],
                ],
            ],
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Heritage Linen Shirt',
                    'price' => 1999,
                    'category_id' => 1,
                    'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800',
                    'description' => 'Breathable premium linen shirt for all-day comfort.',
                    'tags' => ['new', 'featured'],
                    'variants' => ['S', 'M', 'L', 'XL'],
                    'stock' => 30,
                    'is_featured' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Urban Cargo Trousers',
                    'price' => 2499,
                    'category_id' => 2,
                    'image' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800',
                    'description' => 'Tailored cargo fit with stretch blend and utility pockets.',
                    'tags' => ['best_seller'],
                    'variants' => ['30', '32', '34', '36'],
                    'stock' => 22,
                    'is_featured' => true,
                ],
                [
                    'id' => 3,
                    'name' => 'Minimal Leather Sneakers',
                    'price' => 3299,
                    'category_id' => 3,
                    'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800',
                    'description' => 'Low-top leather sneakers with memory sole cushioning.',
                    'tags' => ['sale'],
                    'variants' => ['7', '8', '9', '10'],
                    'stock' => 15,
                    'is_featured' => false,
                ],
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Shirts'],
                ['id' => 2, 'name' => 'Bottomwear'],
                ['id' => 3, 'name' => 'Footwear'],
            ],
            'tokens' => [],
            'otps' => [],
            'carts' => [],
            'orders' => [],
        ];
    }
}
