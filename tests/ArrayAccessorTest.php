<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use PhilipRehberger\RuleEngine\ArrayAccessor;
use PHPUnit\Framework\TestCase;

class ArrayAccessorTest extends TestCase
{
    private ArrayAccessor $accessor;

    protected function setUp(): void
    {
        $this->accessor = new ArrayAccessor;
    }

    // --- Deeply nested arrays ---

    public function test_get_deeply_nested_array_value(): void
    {
        $context = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep-value',
                    ],
                ],
            ],
        ];

        $this->assertSame('deep-value', $this->accessor->get($context, 'level1.level2.level3.level4'));
    }

    public function test_has_deeply_nested_array_path(): void
    {
        $context = [
            'a' => ['b' => ['c' => ['d' => true]]],
        ];

        $this->assertTrue($this->accessor->has($context, 'a.b.c.d'));
    }

    // --- Deeply nested objects ---

    public function test_get_deeply_nested_object_value(): void
    {
        $obj = new \stdClass;
        $obj->user = new \stdClass;
        $obj->user->profile = new \stdClass;
        $obj->user->profile->settings = new \stdClass;
        $obj->user->profile->settings->theme = 'dark';

        $this->assertSame('dark', $this->accessor->get($obj, 'user.profile.settings.theme'));
    }

    public function test_has_deeply_nested_object_path(): void
    {
        $obj = new \stdClass;
        $obj->a = new \stdClass;
        $obj->a->b = new \stdClass;
        $obj->a->b->c = 'end';

        $this->assertTrue($this->accessor->has($obj, 'a.b.c'));
    }

    // --- Mixed array/object traversal ---

    public function test_get_object_inside_array(): void
    {
        $inner = new \stdClass;
        $inner->city = 'Vienna';

        $context = [
            'user' => [
                'address' => $inner,
            ],
        ];

        $this->assertSame('Vienna', $this->accessor->get($context, 'user.address.city'));
    }

    public function test_get_array_inside_object(): void
    {
        $obj = new \stdClass;
        $obj->data = ['items' => ['first', 'second']];

        $this->assertSame(['first', 'second'], $this->accessor->get($obj, 'data.items'));
    }

    public function test_get_alternating_array_and_object_nesting(): void
    {
        $settings = new \stdClass;
        $settings->notifications = ['email' => true, 'sms' => false];

        $context = [
            'app' => [
                'config' => $settings,
            ],
        ];

        $this->assertTrue($this->accessor->get($context, 'app.config.notifications.email'));
        $this->assertFalse($this->accessor->get($context, 'app.config.notifications.sms'));
    }

    public function test_has_mixed_traversal(): void
    {
        $obj = new \stdClass;
        $obj->meta = ['tags' => ['php', 'testing']];

        $context = ['item' => $obj];

        $this->assertTrue($this->accessor->has($context, 'item.meta.tags'));
        $this->assertFalse($this->accessor->has($context, 'item.meta.categories'));
    }

    // --- Missing paths ---

    public function test_get_returns_null_for_missing_top_level_key(): void
    {
        $this->assertNull($this->accessor->get(['a' => 1], 'b'));
    }

    public function test_get_returns_null_for_missing_nested_key(): void
    {
        $this->assertNull($this->accessor->get(['a' => ['b' => 1]], 'a.c'));
    }

    public function test_get_returns_null_for_missing_deep_path(): void
    {
        $this->assertNull($this->accessor->get(['a' => ['b' => 1]], 'a.b.c.d'));
    }

    public function test_has_returns_false_for_missing_top_level_key(): void
    {
        $this->assertFalse($this->accessor->has(['a' => 1], 'b'));
    }

    public function test_has_returns_false_for_missing_nested_key(): void
    {
        $this->assertFalse($this->accessor->has(['a' => ['b' => 1]], 'a.c'));
    }

    public function test_has_returns_false_for_path_through_scalar(): void
    {
        $this->assertFalse($this->accessor->has(['a' => 'string'], 'a.b'));
    }

    public function test_get_returns_null_for_path_through_scalar(): void
    {
        $this->assertNull($this->accessor->get(['a' => 42], 'a.b'));
    }

    public function test_get_returns_null_for_path_through_null_value(): void
    {
        $this->assertNull($this->accessor->get(['a' => null], 'a.b'));
    }

    public function test_has_returns_false_for_path_through_null_value(): void
    {
        $this->assertFalse($this->accessor->has(['a' => null], 'a.b'));
    }

    // --- Edge cases ---

    public function test_get_single_segment_path(): void
    {
        $this->assertSame(42, $this->accessor->get(['key' => 42], 'key'));
    }

    public function test_has_single_segment_path(): void
    {
        $this->assertTrue($this->accessor->has(['key' => 42], 'key'));
    }

    public function test_get_value_that_is_null(): void
    {
        $this->assertNull($this->accessor->get(['key' => null], 'key'));
    }

    public function test_has_key_with_null_value(): void
    {
        $this->assertTrue($this->accessor->has(['key' => null], 'key'));
    }

    public function test_get_value_that_is_false(): void
    {
        $this->assertFalse($this->accessor->get(['flag' => false], 'flag'));
    }

    public function test_get_value_that_is_zero(): void
    {
        $this->assertSame(0, $this->accessor->get(['count' => 0], 'count'));
    }

    public function test_get_value_that_is_empty_string(): void
    {
        $this->assertSame('', $this->accessor->get(['name' => ''], 'name'));
    }

    public function test_get_value_that_is_empty_array(): void
    {
        $this->assertSame([], $this->accessor->get(['items' => []], 'items'));
    }

    public function test_get_from_empty_array_context(): void
    {
        $this->assertNull($this->accessor->get([], 'anything'));
    }

    public function test_has_on_empty_array_context(): void
    {
        $this->assertFalse($this->accessor->has([], 'anything'));
    }

    public function test_get_from_scalar_context(): void
    {
        $this->assertNull($this->accessor->get('not-an-array', 'key'));
    }

    public function test_has_on_scalar_context(): void
    {
        $this->assertFalse($this->accessor->has('not-a-container', 'key'));
    }

    public function test_get_from_null_context(): void
    {
        $this->assertNull($this->accessor->get(null, 'key'));
    }

    public function test_has_on_null_context(): void
    {
        $this->assertFalse($this->accessor->has(null, 'key'));
    }

    public function test_get_with_numeric_string_keys_in_array(): void
    {
        $context = ['0' => 'zero', '1' => 'one'];

        $this->assertSame('zero', $this->accessor->get($context, '0'));
        $this->assertSame('one', $this->accessor->get($context, '1'));
    }

    public function test_get_nested_with_numeric_keys(): void
    {
        $context = ['items' => [0 => 'first', 1 => 'second']];

        $this->assertSame('first', $this->accessor->get($context, 'items.0'));
        $this->assertSame('second', $this->accessor->get($context, 'items.1'));
    }

    public function test_get_object_property_with_null_value(): void
    {
        $obj = new \stdClass;
        $obj->value = null;

        $this->assertNull($this->accessor->get($obj, 'value'));
        $this->assertTrue($this->accessor->has($obj, 'value'));
    }

    public function test_has_returns_false_for_missing_object_property(): void
    {
        $obj = new \stdClass;
        $obj->exists = true;

        $this->assertFalse($this->accessor->has($obj, 'missing'));
    }

    public function test_get_returns_null_for_missing_object_property(): void
    {
        $obj = new \stdClass;

        $this->assertNull($this->accessor->get($obj, 'missing'));
    }

    public function test_traversal_through_object_then_missing_key(): void
    {
        $obj = new \stdClass;
        $obj->data = new \stdClass;
        $obj->data->name = 'test';

        $this->assertNull($this->accessor->get($obj, 'data.nonexistent'));
        $this->assertFalse($this->accessor->has($obj, 'data.nonexistent'));
    }
}
