<?php

    namespace Rules;

    use ClypperTechnology\RolePricing\Rules\Rule;
    use Generators\Rules\RuleGenerator;
    use PHPUnit\Framework\TestCase;

    final class RuleTest extends TestCase
    {
        public function testPercentageReduction()
        {

            // Arrange
            $price = 100;
            $reduction = 50;
            $rule = RuleGenerator::with(Rule::TYPE_PERCENT, $reduction);
            $manual_price_calculation = $price * ($reduction / 100);

            // Act
            $calculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($manual_price_calculation, $calculated_price);
            $this->assertTrue($calculated_price < $price);
        }

        public function testPercentageAdd()
        {

            // Arrange
            $price = 100;
            $increase = 50;
            $rule = RuleGenerator::with(Rule::TYPE_PERCENT_ADD, $increase);
            $manual_price_calculation = $price * (1 + ($increase / 100));

            // Act
            $caclculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($manual_price_calculation, $caclculated_price);
            $this->assertTrue($caclculated_price > $price);
        }

        public function testFixed()
        {

            // Arrange
            $price = 100;
            $decrease = 99;
            $rule = RuleGenerator::with(Rule::TYPE_FIXED, $decrease);
            $manual_price_calculation = $price - $decrease;

            // Act
            $caclculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($manual_price_calculation, $caclculated_price);
            $this->assertTrue($caclculated_price < $price);
        }

        public function testFixedAdd()
        {

            // Arrange
            $price = 100;
            $increase = 99;
            $rule = RuleGenerator::with(Rule::TYPE_FIXED_ADD, $increase);
            $manual_price_calculation = $price + $increase;

            // Act
            $caclculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($manual_price_calculation, $caclculated_price);
            $this->assertTrue($caclculated_price > $price);
        }

        public function testFixedSet()
        {

            // Arrange
            $price = 100;
            $fixed = 9;
            $rule = RuleGenerator::with(Rule::TYPE_FIXED_SET, $fixed);

            // Act
            $caclculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($fixed, $caclculated_price);
        f
    }
