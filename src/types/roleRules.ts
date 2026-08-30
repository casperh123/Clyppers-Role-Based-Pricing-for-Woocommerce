import { ItemRule } from "./ItemRule";
import { PricingRule } from "./pricingRule";




export interface RoleRules {
  id: number;
  role_name: string;
  role_slug: string;
  rule_active: 'on' | '';
  global_rule: PricingRule;
  category_rule: PricingRule;
  categories: number[];
  products: ItemRule[];
  single_categories: ItemRule[];
}
