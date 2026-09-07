import { Button, ComboboxControl } from "@wordpress/components"
import { useDebounce } from "@wordpress/compose";
import { useState, useTransition } from "react"

export interface SearchItem {
  id: number,
  image_url?: string,
}

export interface ItemSearchProps<T extends SearchItem> {
  searchItems: (search: string) => Promise<T[]>,
  displayItem: (item: T) => DisplayItem,
  onItemAdded: (item: T) => void,
  modalTitle: string,
  addedItems?: Array<T>,
}

export interface DisplayItem {
  value: string,
  label: string
}

export const ItemSearch = <T extends SearchItem>({
  searchItems,
  displayItem,
  onItemAdded,
  modalTitle,
  addedItems = [],
}: ItemSearchProps<T>) => {
  const [items, setItems] = useState<T[]>([]);
  const options = items.map(displayItem);
  const [isPending, setTransition] = useTransition();


  const runSearch = (inputValue: string) => {
    setTransition(async () => {
      const fetchedItems = await searchItems(inputValue);
      const filteredItems = fetchedItems.filter(item => !isAdded(item));
      setItems(filteredItems);
    })
  };

  const onInputChanged = useDebounce(runSearch, 500);


  const getItem = (value?: string) => {
    return items.find(p => String(p.id) === value);
  }

  const selectItem = (item?: T) => {
    if(!item || isAdded(item)) return;
    onItemAdded(item);
  }

  const isAdded = (item: T) => {
    return addedItems.some(element => item.id == element.id);
  }

  return (
    <ComboboxControl
      label={modalTitle}
      options={options}
      onFilterValueChange={onInputChanged}
      onChange={(value) => selectItem(getItem(value ?? ""))}
      isLoading={isPending}
      __experimentalRenderItem={({ item: option }) => {
        const item = getItem(option.value);
        if (!item) return;

        const alreadyAdded = isAdded(item);

        return (
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            {item.image_url && (
              <img
                src={item.image_url}
                alt=""
                style={{ width: 45, height: 45, objectFit: 'cover', borderRadius: 2 }}
              />
            )}
            <span style={{ flex: 1 }}>{option.label}</span>
            <Button
              variant={alreadyAdded ? "secondary" : "primary"}
              disabled={alreadyAdded}
              onClick={(e: React.MouseEvent) => {
                if(alreadyAdded) { return; }
                e.stopPropagation();
                onItemAdded(item);
              }}
            >
              {alreadyAdded ? "Added" : "Add"}
            </Button>
          </div>
        );
      }}
    />
  )
}
