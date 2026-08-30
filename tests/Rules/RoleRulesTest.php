<?php

    namespace Rules;

    use ClypperTechnology\RolePricing\Rules\Rule;
    use Generators\Rules\ItemRuleGenerator;
    use Generators\Rules\RoleRuleGenerator;
    use Generators\Rules\RuleGenerator;
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
            $global_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 100);
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
            $category_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 100);
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
            $rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, $rule_price);
            $product_rule = ItemRuleGenerator::with($product_id, "test_product", $rule);
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

            $product_rule = ItemRuleGenerator::with($product_id, "test_product", RuleGenerator::with(Rule::TYPE_FIXED_SET, 10));
            $category_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 50);
            $global_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 100);

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

            $product_rule = ItemRuleGenerator::with($product_id, "p", RuleGenerator::with(Rule::TYPE_FIXED_SET, 10));
            $single_category_rule = ItemRuleGenerator::with(100, "sc", RuleGenerator::with(Rule::TYPE_FIXED_SET, 25));
            $category_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 50);
            $global_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 100);

            $role_rules = RoleRuleGenerator::withItemRules(
                [$product_id => $product_rule], [100 => $single_category_rule], $global_rule, $category_rule, $category_ids
            );

            $applicable_rule = $role_rules->get_applicable_rule($product_id, $category_ids);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(10, $calculated_price); // product still wins over everything
        }

        public function testProductRuleDoesNotMatchDifferentProductId() {
            $product_rule = ItemRuleGenerator::with(42, "p", RuleGenerator::with(Rule::TYPE_FIXED_SET, 10));
            $global_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 100);
            $role_rules = RoleRuleGenerator::withItemRules([42 => $product_rule], [], $global_rule);

            $applicable_rule = $role_rules->get_applicable_rule(999, []);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(100, $calculated_price);
        }

        public function testCategoryRuleMatchesWhenOneOfSeveralCategoriesHasARule() {
            $category_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 50);
            $role_rules = RoleRuleGenerator::withItemRules([], [], RuleGenerator::Random(), $category_rule, [100]);

            $applicable_rule = $role_rules->get_applicable_rule(-1, [200, 300, 100]);
            $calculated_price = $applicable_rule->calculatePrice(10_000, 0);

            $this->assertEquals(50, $calculated_price);
        }

        public function testQuantityRuleAppliesWhenQuantityMeetsMinimum() {

            // Arrange: regular rule gives 100 off, quantity rule gives 500 off, min qty is 10
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 500, Rule::TYPE_FIXED);

            // Act: buying exactly the minimum quantity
            $price = $rule->calculatePrice(1000, 10, 10);

            // Assert: quantity rule (500 off) wins, not the regular rule (100 off)
            $this->assertEquals(500, $price);
        }

        public function testQuantityRuleAppliesWhenQuantityExceedsMinimum() {

            // Arrange
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 500, Rule::TYPE_FIXED);

            // Act: buying more than the minimum
            $price = $rule->calculatePrice(1000, 10, 50);

            // Assert
            $this->assertEquals(500, $price);
        }

        public function testRegularRuleAppliesWhenQuantityBelowMinimum() {

            // Arrange
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 500, Rule::TYPE_FIXED);

            // Act: buying fewer than the minimum required for the quantity discount
            $price = $rule->calculatePrice(1000, 10, 5);

            // Assert: falls back to regular rule (100 off), not the quantity rule
            $this->assertEquals(900, $price);
        }

        public function testRegularRuleAppliesWhenNoQuantityRuleIsSet() {

            // Arrange: quantity_value of 0 means has_quantity_value() is false
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 0, Rule::TYPE_FIXED);

            // Act: even meeting a "minimum" of 0, there's no quantity rule to apply
            $price = $rule->calculatePrice(1000, 0, 100);

            // Assert
            $this->assertEquals(900, $price);
        }

        public function testNoRuleAppliesWhenNeitherValueNorQuantityValueSet() {

            // Arrange: both value and quantity_value are 0/unset
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 0, 0, Rule::TYPE_FIXED);

            // Act
            $price = $rule->calculatePrice(1000, 0, 100);

            // Assert: no applicable rule at all
            $this->assertNull($price);
        }

        public function testQuantityRuleCanUseADifferentTypeThanRegularRule() {

            // Arrange: regular rule is a fixed discount, but the bulk-quantity rule
            // switches to a percentage discount instead
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 50, Rule::TYPE_PERCENT);

            // Act: quantity threshold met, so quantity_value/quantity_value_type apply
            $price = $rule->calculatePrice(1000, 10, 20);

            // Assert: 50% off 1000 = 500, not the fixed-100-off regular rule
            $this->assertEquals(500, $price);
        }

        public function testQuantityBoundaryIsInclusive() {
            // Arrange
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, 100, 500, Rule::TYPE_FIXED);

            // Act
            $price_at_boundary = $rule->calculatePrice(1000, 10, 10);
            $price_just_below = $rule->calculatePrice(1000, 10, 9);

            // Assert: >= is inclusive, so exactly-at-minimum should get the quantity rule,
            // one below should not
            $this->assertEquals(500, $price_at_boundary);
            $this->assertEquals(900, $price_just_below);
        }
    }