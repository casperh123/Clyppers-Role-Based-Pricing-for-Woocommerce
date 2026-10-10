import { RuleListItem } from "./listItems/RuleListItem";
import { __ } from "@wordpress/i18n";
import type { FieldArrayWithId } from "react-hook-form";
import type { RoleRules } from "@/types/roleRules";
import { RuleKey } from "@/types/RuleKey";

interface RuleListProps {
  fields: FieldArrayWithId<RoleRules, RuleKey, "id">[];
  ruleKey: RuleKey,
  onRemove: (index: number) => void;
}

export const RuleList = ({
  fields,
  ruleKey,
  onRemove,
}: RuleListProps) => {
  return (
    <table className="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th style={{width: 70}}></th>
          <th>{__('Name', 'clyppers-role-based-pricing-for-woocommerce')}</th>

          { ruleKey == "products" && (
            <th style={{width: 150}}>{__('Price', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          )}
          <th>{__('Rule type', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          <th style={{width: 130}}>{__('Value', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          <th style={{width: 130}} className="quantity-column">{__('Min quantity', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          <th className="quantity-column">{__('Quantity rule type', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          <th style={{width: 130}} className="quantity-column">{__('Quantity rule value', 'clyppers-role-based-pricing-for-woocommerce')}</th>
          <th style={{width: 50}}></th>
        </tr>
      </thead>

      <tbody>
        {fields.map((field, index) => (
          <RuleListItem
            key={field.id}
            ruleKey={ruleKey}
            index={index}
            onRemove={() => onRemove(index)}
          />
        ))}
      </tbody>
    </table>
  );
};
