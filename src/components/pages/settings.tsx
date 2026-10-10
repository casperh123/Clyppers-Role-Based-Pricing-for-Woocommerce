import { __ } from "@wordpress/i18n";
import { SettingsService } from "@/services/settingsService";
import { Settings as PluginSettings } from "@/types/settings";
import { Button, Card, CardBody, Notice, Spinner, ToggleControl } from "@wordpress/components";
import { arrowLeft } from "@wordpress/icons";
import { useEffect, useState, useTransition } from "react";
import { useNavigate } from "react-router-dom";

export function Settings() {
  const [settings, setSettings] = useState<PluginSettings>();
  const [error, setError] = useState<string>();
  const [isSaving, startTransition] = useTransition();
  const navigate = useNavigate();

  useEffect(() => {
    const load = async () => {
      try {
        setSettings(await SettingsService.getSettings());
      } catch {
        setError(__('The settings could not be loaded. Reload the page to try again.', 'clyppers-role-based-pricing-for-woocommerce'));
      }
    };

    void load();
  }, []);

  const update = (changes: Partial<PluginSettings>) => {
    if (!settings) {
      return;
    }

    const previous = settings;
    setSettings({ ...settings, ...changes });
    setError(undefined);

    startTransition(async () => {
      try {
        setSettings(await SettingsService.updateSettings({ ...previous, ...changes }));
      } catch {
        // Keep the toggle showing what is actually saved.
        setSettings(previous);
        setError(__('Your change could not be saved. Please try again.', 'clyppers-role-based-pricing-for-woocommerce'));
      }
    });
  };

  return (
    <div>
      <div className="row" style={{ marginBottom: 40 }}>
        <Button icon={arrowLeft} onClick={() => navigate("/")} label={__('Back to roles', 'clyppers-role-based-pricing-for-woocommerce')} />
        <h1>{__('Settings', 'clyppers-role-based-pricing-for-woocommerce')}</h1>
      </div>
      <div className="roles-list">
        { error && (
          <Notice status="error" onRemove={() => setError(undefined)}>{error}</Notice>
        )}
        <Card>
          <CardBody>
            { settings && (
              <ToggleControl
                __nextHasNoMarginBottom
                label={__('Show the "Volume discount!" badge', 'clyppers-role-based-pricing-for-woocommerce')}
                help={__('Shown next to the sale badge on products that have a quantity discount for the visitor\'s role. The unit price box on the product page is not affected.', 'clyppers-role-based-pricing-for-woocommerce')}
                checked={settings.show_volume_badge}
                disabled={isSaving}
                onChange={(show_volume_badge) => update({ show_volume_badge })}
              />
            )}
            { !settings && !error && <Spinner /> }
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
