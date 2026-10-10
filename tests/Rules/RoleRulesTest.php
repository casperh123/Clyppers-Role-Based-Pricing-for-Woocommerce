<?php

    namespace Rules;

    use ClypperTechnology\RolePricing\Rules\RoleRules;
    use ClypperTechnology\RolePricing\Rules\Rule;
    use ClypperTechnology\RolePricing\Rules\RuleType;
    use Generators\Rules\ItemRuleGenerator;
    use Generators\Rules\RoleRuleGenerator;
    use Generators\Rules\RuleGenerator;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;

    class RoleRulesTest extends TestCase
    {
        public function testGetRuleCountReturnsCount() {

            // Arrange
            $products = 10;
            $categories = 9;
            $total = $products + $categories;
            $productRules = ItemRuleGenerator::RandomCollection($products);
            $categoryRules = ItemRuleGenerator::RandomCollection($categories);
            $roleRule = RoleRuleGenerator::withItemRules($productRules, $categoryRules);

            // Act
            $count = $roleRule->get_rule_count();

            // Assert
            $this->assertEquals($total, $count);
        }

        // Test that ProductRule is the chosen rule if it matches
        // Test that SingleCategoryRule is the chosen rule if it matches
        // Test that Category Rule if it matches
        public function testGetApplicableRuleReturnsGlobalRule() {

            // Arrange
            $products = 100;
            $categories = 100;
            $products = ItemRuleGenerator::RandomCollection($products);
            $single_categories = ItemRuleGenerator::RandomCollection($categories);
            $global_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 100);
            $role_rule = RoleRuleGenerator::withItemRules($products, $single_categories, $global_rule);

            // Act
            $applicable_rule = $role_rule->get_applicable_rule(-1, []);
            $calculated_price = $applicable_rule->calculatePrice(10000000, 0);

            // Assert
            $this->assertEquals(100, $calculated_price);
        }

        public function testGetApplicableRuleReturnsCategoryRule() {

            // Arrange
            $products = 100;
            $categories = 100;
            $category_ids = [ 100 ];
            $products = ItemRuleGenerator::RandomCollection($products);
            $single_categories = ItemRuleGenerator::RandomCollection($categories);
            unset($single_categories[100]); // a single-category rule for 100 would rightly win over the category rule
            $category_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 100);
            $global_rule = RuleGenerator::Random();
            $role_rule = RoleRuleGenerator::withItemRules($products, $single_categories, $global_rule, $category_rule, $category_ids);

            // Act
            $applicable_rule = $role_rule->get_applicable_rule(-1, [ 100 ] );
            $calculated_price = $applicable_rule->calculatePrice(10000000, 0);

            // Assert
            $this->assertEquals(100, $calculated_price);
        }

        public function testGetApplicableRuleReturnsProductRule() {

            // Arrange
            $product_id = 42;
            $rule_price = 999;
            $rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, $rule_price);
            $product_rule = ItemRuleGenerator::with_rules($rule);
            $global_rule = RuleGenerator::Random();
            $role_rules = RoleRuleGenerator::withItemRules([$product_id => $product_rule], [], $global_rule);

            // Act
            $applicable_rule = $role_rules->get_applicable_rule($product_id, []);
            $calculated_price =  $applicable_rule->calculatePrice(10_000, 0);

            // Assert
            $this->assertEquals($rule_price, $calculated_price);
        }

        public function testProductRuleTakesPrecedenceOverCategoryAndGlobalRule() {
            $product_id = 42;
            $category_ids = [100];

            $product_rule = ItemRuleGenerator::with($product_id, "test_product", RuleGenerator::with(RuleType::TYPE_FIXED_SET, 10));
            $category_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 50);
            $global_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 100);

            $role_rules = RoleRuleGenerator::withItemRules(
                [$product_id => $product_rule], [], $global_rule, $category_rule, $category_ids
            );

            $applicable_rule = $role_rules->get_applicable_rule($product_id, $category_ids);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            // Product rule (10) must win over category (50) and global (100)
            $this->assertEquals(10, $calculated_price);
        }

        public function testFullRulePrecedenceOrder() {
            $product_id = 42;
            $category_ids = [100];

            $product_rule = ItemRuleGenerator::with($product_id, "p", RuleGenerator::with(RuleType::TYPE_FIXED_SET, 10));
            $single_category_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 25);
            $category_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 50);
            $global_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 100);

            $role_rules = RoleRuleGenerator::withItemRules(
                [$product_id => $product_rule], [100 => $single_category_rule], $global_rule, $category_rule, $category_ids
            );

            $applicable_rule = $role_rules->get_applicable_rule($product_id, $category_ids);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(10, $calculated_price); // product still wins over everything
        }

        public function testProductRuleDoesNotMatchDifferentProductId() {
            $product_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 10);
            $global_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 100);
            $role_rules = RoleRuleGenerator::withItemRules([42 => $product_rule], [], $global_rule);

            $applicable_rule = $role_rules->get_applicable_rule(999, []);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(100, $calculated_price);
        }

        public function testFromUpdateKeepsIdAndRoleOfExistingRule() {

            // Arrange
            $existing = RoleRuleGenerator::empty();
            $body = self::asRequestBody(RoleRuleGenerator::empty());
            $body['id'] = $existing->id + 1;
            $body['role_slug'] = 'administrator';

            // Act
            $updated = RoleRules::from_update($existing, $body);

            // Assert
            $this->assertSame($existing->id, $updated->id);
            $this->assertSame($existing->role_slug, $updated->role_slug);
        }

        public function testFromUpdateAppliesRuleChangesFromRequest() {

            // Arrange
            $existing = RoleRuleGenerator::empty(active: false);
            $body = self::asRequestBody(RoleRuleGenerator::withItemRules(
                ItemRuleGenerator::RandomCollection(3),
                [],
                RuleGenerator::with(RuleType::TYPE_PERCENT, 15)
            ));

            // Act
            $updated = RoleRules::from_update($existing, $body);

            // Assert
            $this->assertTrue($updated->rule_active);
            $this->assertEquals(RuleType::TYPE_PERCENT, $updated->global_rule->type);
            $this->assertEquals(15, $updated->global_rule->value);
            $this->assertSame(3, $updated->get_rule_count());
        }

        public function testCategoryRuleMatchesWhenOneOfSeveralCategoriesHasARule() {
            $category_rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, 50);
            $role_rules = RoleRuleGenerator::withItemRules([], [], RuleGenerator::Random(), $category_rule, [100]);

            $applicable_rule = $role_rules->get_applicable_rule(-1, [200, 300, 100]);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(50, $calculated_price);
        }

        public function testFromArrayWithNothingIsAnInactiveRuleWithoutPricing() {
            $rule = RoleRules::from_array([]);

            $this->assertSame(0, $rule->id);
            $this->assertSame('', $rule->role_slug);
            $this->assertFalse($rule->rule_active);
            $this->assertNull($rule->global_rule);
            $this->assertNull($rule->category_rule);
            $this->assertSame([], $rule->categories);
            $this->assertSame(0, $rule->get_rule_count());
            $this->assertNull($rule->get_applicable_rule(1, [1], 1));
        }

        public static function malformedFields(): array {
            return [
                'global_rule not an array'       => [['global_rule' => 'percent']],
                'category_rule not an array'     => [['category_rule' => 5]],
                'categories not an array'        => [['categories' => '1,2,3']],
                'products not an array'          => [['products' => 'all']],
                'single_categories not an array' => [['single_categories' => true]],
                'items that are not arrays'      => [['products' => ['x', 1, null]]],
                'unknown rule type'              => [['global_rule' => ['type' => 'free', 'value' => 100]]],
            ];
        }

        #[DataProvider('malformedFields')]
        public function testMalformedFieldsNeverProduceAPrice(array $fields) {
            $rule = RoleRules::from_update(RoleRuleGenerator::empty(), $fields + ['rule_active' => 'on']);

            $this->assertSame(0, $rule->get_rule_count());
            $this->assertNull($rule->get_applicable_rule(1, [1], 1));
        }

        public function testCategoriesKeepOnlyPositiveUniqueIds() {
            $rule = RoleRules::from_array(['categories' => [3, '3', '7', 0, -2, 'abc', [1], 12.0]]);

            $this->assertSame([3, 7, 12], $rule->categories);
        }

        public static function activeValues(): array {
            return [
                "'on'"  => ['on', true],
                'true'  => [true, true],
                "''"    => ['', false],
                'false' => [false, false],
                "'1'"   => ['1', false],
                'array' => [['on'], false],
            ];
        }

        #[DataProvider('activeValues')]
        public function testRuleActiveAcceptsOnlyOnOrTrue(mixed $value, bool $expected) {
            $this->assertSame($expected, RoleRules::from_array(['rule_active' => $value])->rule_active);
        }

        public function testStoredRuleRoundTrips() {
            $rule = RoleRuleGenerator::withItemRules(
                ItemRuleGenerator::RandomCollection(3),
                ItemRuleGenerator::RandomCollection(2),
                RuleGenerator::with(RuleType::TYPE_PERCENT, 10),
                RuleGenerator::with(RuleType::TYPE_FIXED, 5),
                [4, 8],
            );

            $this->assertEquals($rule, RoleRules::from_array(self::asRequestBody($rule)));
        }

        /**
         * The rule as the admin app sends it: JSON-encoded, then decoded by the REST API.
         */
        private static function asRequestBody(RoleRules $rule): array {
            return json_decode(json_encode($rule->to_array()), true);
        }
    }