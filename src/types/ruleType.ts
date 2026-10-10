import { __ } from "@wordpress/i18n";

export type RuleType =
  | "percent"
  | "percent_add"
  | "fixed"
  | "fixed_add"
  | "fixed_set"
  | "";

export const ruleTypeFormValues: {
  label: string;
  value: RuleType;
}[] = [
  { label: __('Reduce by percent (%)', 'clyppers-role-based-pricing-for-woocommerce'), value: "percent" },
  { label: __('Increase by percent (%)', 'clyppers-role-based-pricing-for-woocommerce'), value: "percent_add" },
  { label: __('Reduce by fixed value', 'clyppers-role-based-pricing-for-woocommerce'), value: "fixed" },
  { label: __('Increase by fixed value', 'clyppers-role-based-pricing-for-woocommerce'), value: "fixed_add" },
  { label: __('Fixed price', 'clyppers-role-based-pricing-for-woocommerce'), value: "fixed_set" },
];
