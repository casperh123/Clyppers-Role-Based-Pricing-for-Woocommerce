import { Badge, CollapsibleCard } from "@wordpress/ui"
import { __ } from "@wordpress/i18n";
import { Button, Modal } from "@wordpress/components";
import { useState } from "react";
import { RuleList } from "../controls/RuleList";
import { useFieldArray, useFormContext } from "react-hook-form";
import { RoleRules } from "@/types/roleRules";
import { AddRule } from "./AddRule";
import { RuleKey } from "@/types/RuleKey";
import { ItemRule } from "@/types/ItemRule";
import { Icon, plus } from "@wordpress/icons";

interface RulesPanelProps<T> {
  createRule: (item: T) => ItemRule,
  onSearch: (search: string) => Promise<T[]>,
  ruleKey: RuleKey,
  title: string,
  startOpen?: boolean,
  modalTitle: string
}

interface RuleItem {
  id: number,
  name: string
}

export const RulesPanel = <T extends RuleItem>({ 
  ruleKey,
  onSearch,
  createRule,
  title,
  modalTitle,
  startOpen = false
}: RulesPanelProps<T>) => {
  const [addRule, setAddRule] = useState<boolean>(false);
  const { control } = useFormContext<RoleRules>();

  const { fields, append, remove } = useFieldArray({
    control,
    name: ruleKey
  })

  const onItemAdded = (item: T) => {
    append(createRule(item));
  }

  return (
    <CollapsibleCard.Root defaultOpen={startOpen}>
      <CollapsibleCard.Header>
        <div className="row" style={{alignItems: "center"}}>
          <div className="row">
          <h2>{title}</h2>
          <Badge intent="draft">
            {`${fields.length}`}
          </Badge>
          </div>
          {/* The header toggles the panel, so keep clicks and key presses on this button from reaching it. */}
          <Button
            isDestructive={addRule}
            onClick={(e: React.MouseEvent) => { e.stopPropagation(); setAddRule(!addRule); }}
            onKeyDown={(e: React.KeyboardEvent) => e.stopPropagation()}
            label={modalTitle}
          >
            <Icon icon={ plus }/>
          </Button>
        </div>
      </CollapsibleCard.Header>

      <CollapsibleCard.Content>
        <div className="col">
         { addRule && (
           <Modal onRequestClose={() => setAddRule(false)} size="medium">
              <AddRule onAdd={onItemAdded} onSearch={onSearch} ruleKey={ruleKey} modalTitle={modalTitle}/>
           </Modal>
         )}
          
         { fields.length == 0 ? (
          <p>{__('There are no rules yet!', 'clyppers-role-based-pricing-for-woocommerce')}</p>
         ) : (
          <RuleList fields={fields} onRemove={remove} ruleKey={ruleKey}/>
         )}
        </div>
      </CollapsibleCard.Content>
    </CollapsibleCard.Root>
  )
}
