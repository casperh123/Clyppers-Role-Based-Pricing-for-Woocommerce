import { RoleRules } from '@/types/roleRules';
import { ApiPath } from '@/shared/apiPaths';
import apifetch from '@wordpress/api-fetch';




export class RuleService {
  private static resource = ApiPath.rulesPath();

  public static async getRule(id: number): Promise<RoleRules> {
    return apifetch<RoleRules>({
      path: ApiPath.rulePath(id)
    });
  }

  public static async getAllRules(): Promise<RoleRules[]> {
    return apifetch<RoleRules[]>({ path: this.resource});
  }

  public static async addRules(slug: string): Promise<number> {
    return apifetch<number>({
      path: this.resource,
      method: "POST",
      data: {
        slug: slug
      }
    });
  }

  public static async updateRules(rule: RoleRules): Promise<void> {
    return apifetch({
      path: ApiPath.rulePath(rule.id),
      method: "POST",
      data: rule,
    });
  }
}
