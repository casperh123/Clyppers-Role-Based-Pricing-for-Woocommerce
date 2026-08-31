import { RuleType } from "./ruleType";

export interface PricingRule {
  type: RuleType;
  value: string;
}
