<?php

namespace Rules;

use ClypperTechnology\RolePricing\Rules\ItemRule;
use ClypperTechnology\RolePricing\Rules\Rule;
use Generators\Rules\ItemRuleGenerator;
use PHPUnit\Framework\TestCase;

class ItemRuleTest extends TestCase
{
    public function testRegularRuleApplies(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100)
        );

        $price = $rule->calculatePrice(1000);

        $this->assertEquals(900, $price);
    }

    public function testQuantityRuleAppliesWhenQuantityMeetsMinimum(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $price = $rule->calculatePrice(1000, 10);

        $this->assertEquals(500, $price);
    }

    public function testQuantityRuleAppliesWhenQuantityExceedsMinimum(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $price = $rule->calculatePrice(1000, 50);

        $this->assertEquals(500, $price);
    }

    public function testRegularRuleAppliesWhenQuantityIsBelowMinimum(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $price = $rule->calculatePrice(1000, 5);

        $this->assertEquals(900, $price);
    }

    public function testRegularRuleAppliesWhenNoQuantityRuleIsSet(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100)
        );

        $price = $rule->calculatePrice(1000, 100);

        $this->assertEquals(900, $price);
    }

    public function testOriginalPriceIsReturnedWhenNoRuleIsSet(): void
    {
        $rule = ItemRuleGenerator::with_rules();

        $price = $rule->calculatePrice(1000, 100);

        $this->assertEquals(null, $price);
    }

    public function testQuantityRuleCanUseDifferentTypeThanRegularRule(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_PERCENT, 50),
            min_qty: 10
        );

        $price = $rule->calculatePrice(1000, 20);

        $this->assertEquals(500, $price);
    }

    public function testQuantityBoundaryIsInclusive(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $priceAtBoundary = $rule->calculatePrice(1000, 10);
        $priceJustBelow = $rule->calculatePrice(1000, 9);

        $this->assertEquals(500, $priceAtBoundary);
        $this->assertEquals(900, $priceJustBelow);
    }

    public function testQuantityReductionAppliesWhenQuantityMeetsMinimum(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $this->assertTrue(
            $rule->quantity_reduction_applies(10)
        );
    }

    public function testQuantityReductionDoesNotApplyWhenQuantityIsBelowMinimum(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $this->assertFalse(
            $rule->quantity_reduction_applies(9)
        );
    }

    public function testQuantityReductionDoesNotApplyWhenQuantityRuleHasNoValue(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            quantity_rule: new Rule(Rule::TYPE_FIXED, 0),
            min_qty: 10
        );

        $this->assertFalse(
            $rule->quantity_reduction_applies(10)
        );
    }

    public function testReductionAppliesForRegularRule(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            rule: new Rule(Rule::TYPE_FIXED, 100)
        );

        $this->assertTrue(
            $rule->reduction_applies(1)
        );
    }

    public function testReductionAppliesForQuantityRule(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $this->assertTrue(
            $rule->reduction_applies(10)
        );
    }

    public function testReductionDoesNotApplyWhenNoRuleHasValue(): void
    {
        $rule = ItemRuleGenerator::with_rules();

        $this->assertFalse(
            $rule->reduction_applies(100)
        );
    }

    public function testQuantityReductionMessageIsNullWhenNoQuantityRuleExists(): void
    {
        $rule = ItemRuleGenerator::with_rules();

        $this->assertNull(
            $rule->quantity_reduction_message()
        );
    }

    public function testQuantityReductionMessageIsNullWhenMinimumQuantityIsZero(): void
    {
        $rule = ItemRuleGenerator::with_rules(
            quantity_rule: new Rule(Rule::TYPE_FIXED, 100),
            min_qty: 0
        );

        $this->assertNull(
            $rule->quantity_reduction_message()
        );
    }

    public function testToArrayContainsItemRuleData(): void
    {
        $rule = ItemRuleGenerator::with(
            id: 123,
            name: 'Test rule',
            rule: new Rule(Rule::TYPE_FIXED, 100),
            quantity_rule: new Rule(Rule::TYPE_FIXED, 500),
            min_qty: 10
        );

        $data = $rule->to_array();

        $this->assertEquals(123, $data['id']);
        $this->assertEquals('Test rule', $data['name']);
        $this->assertArrayHasKey('rule', $data);
        $this->assertArrayHasKey('quantity_rule', $data);
        $this->assertEquals(10, $data['min_qty']);
    }

    public function testCanCreateItemRuleFromArray(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test rule',
            'rule' => [
                'type' => Rule::TYPE_FIXED,
                'value' => 100,
            ],
            'quantity_rule' => [
                'type' => Rule::TYPE_FIXED,
                'value' => 500,
            ],
            'min_qty' => 10,
        ];

        $rule = ItemRule::from_array($data);

        $this->assertEquals(123, $rule->id);
        $this->assertEquals('Test rule', $rule->name);
        $this->assertEquals(10, $rule->min_quantity);
    }
}
