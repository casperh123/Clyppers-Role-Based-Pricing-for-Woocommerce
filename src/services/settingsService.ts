import { ApiPath } from "@/shared/apiPaths";
import { Settings } from "@/types/settings";
import apiFetch from "@wordpress/api-fetch";

export class SettingsService {
  public static async getSettings(): Promise<Settings> {
    return apiFetch<Settings>({
      path: ApiPath.settingsPath(),
    });
  }

  public static async updateSettings(settings: Settings): Promise<Settings> {
    return apiFetch<Settings>({
      path: ApiPath.settingsPath(),
      method: "PUT",
      data: settings,
    });
  }
}
