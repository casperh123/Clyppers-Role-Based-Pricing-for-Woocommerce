<?php

    namespace Rules;

    use ClypperTechnology\RolePricing\Rules\Rule;
    use ClypperTechnology\RolePricing\Rules\RuleType;
    use Generators\Rules\RuleGenerator;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;

    final class RuleTest extends TestCase
    {
        public function testPercentageReduction()
        {

            // Arrange
            $price = 100;
            $reduction = 50;
            $rule = RuleGenerator::with(RuleType::TYPE_PERCENT, $reduction);
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
            $rule = RuleGenerator::with(RuleType::TYPE_PERCENT_ADD, $increase);
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
            $rule = RuleGenerator::with(RuleType::TYPE_FIXED, $decrease);
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
            $rule = RuleGenerator::with(RuleType::TYPE_FIXED_ADD, $increase);
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
            $rule = RuleGenerator::with(RuleType::TYPE_FIXED_SET, $fixed);

            // Act
            $caclculated_price = $rule->calculatePrice($price);

            // Assert
            $this->assertEquals($fixed, $caclculated_price);
        }

        public function testEmptyTypeNeverAppliesAndLeavesThePriceAlone()
        {
            $rule = RuleGenerator::with(RuleType::EMPTY, 50);

            $this->assertFalse($rule->rule_applies());
            $this->assertNull($rule->calculatePrice(100));
        }

        public static function unusableRuleData(): array
        {
            return [
                'null'              => [null],
                'string'            => ['percent'],
                'empty array'       => [[]],
                'unknown type'      => [['type' => 'free_money', 'value' => 50]],
                'type not a string' => [['type' => ['percent'], 'value' => 50]],
                'missing type'      => [['value' => 50]],
            ];
        }

        #[DataProvider('unusableRuleData')]
        public function testUnusableDataBecomesAnEmptyRule(mixed $data)
        {
            $rule = Rule::from_array($data);

            $this->assertSame(RuleType::EMPTY, $rule->type);
            $this->assertFalse($rule->rule_applies());
        }

        public static function unusableValues(): array
        {
            return [
                'negative'    => [-10],
                'non-numeric' => ['ten'],
                'array'       => [[10]],
                'missing'     => [null],
            ];
        }

        #[DataProvider('unusableValues')]
        public function testUnusableValueBecomesZero(mixed $value)
        {
            $rule = Rule::from_array(['type' => 'percent', 'value' => $value]);

            $this->assertSame(0.0, $rule->value);
            $this->assertFalse($rule->rule_applies());
        }

        public function testValidDataRoundTrips()
        {
            $rule = Rule::from_array(['type' => 'fixed', 'value' => '12.5']);

            $this->assertSame(RuleType::TYPE_FIXED, $rule->type);
            $this->assertSame(12.5, $rule->value);
            $this->assertEquals($rule, Rule::from_array(json_decode(json_encode($rule->to_array()), true)));
        }
    }
