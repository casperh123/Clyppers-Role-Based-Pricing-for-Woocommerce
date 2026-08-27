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
  { label: "Reduce by percent (%)", value: "percent" },
  { label: "Increase by percent (%)", value: "percent_add" },
  { label: "Reduce by fixed value", value: "fixed" },
  { label: "Increase by fixed value", value: "fixed_add" },
  { label: "Fixed price", value: "fixed_set" },
];
