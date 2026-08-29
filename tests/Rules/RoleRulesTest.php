<?php

namespace Rules;

use Generators\Rules\ItemRuleGenerator;
use Generators\Rules\RoleRuleGenerator;
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
}