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
            $single_category_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 25);
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
            $product_rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, 10);
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
    }