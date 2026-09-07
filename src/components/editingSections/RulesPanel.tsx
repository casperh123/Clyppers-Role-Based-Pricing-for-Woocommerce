import { Badge, CollapsibleCard } from "@wordpress/ui"
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
        <div className="row">
          <h2>{title}</h2>
          <Badge intent="draft">
            {`${fields.length}`}
          </Badge>
        </div>
      </CollapsibleCard.Header>

      <CollapsibleCard.Content>
        <div className="col">
          <div className="row">
            <Button isDestructive={addRule} variant="primary" onClick={() => setAddRule(!addRule)}>{ addRule ? "Close" : <Icon icon={ plus }/>}</Button>
          </div>
        
         { addRule && (
           <Modal onRequestClose={() => setAddRule(false)} size="medium">
              <AddRule onAdd={onItemAdded} onSearch={onSearch} ruleKey={ruleKey} modalTitle={modalTitle}/>
           </Modal>
         )}
          
         { fields.length == 0 ? (
          <p>There are no rules yet!</p>
         ) : (
          <RuleList fields={fields} onRemove={remove} ruleKey={ruleKey}/>
         )}
        </div>
      </CollapsibleCard.Content>
    </CollapsibleCard.Root>
  )
}
