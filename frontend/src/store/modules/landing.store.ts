import Api from "@/common/api";
import appInstance from "@/main";
import Landing from "@/types/landing/Landing";
import LandingLink from "@/types/landing/LandingLink";
import { Getters, Mutations, Actions, Module } from "vuex-smart-module";

// State
class LandingState {
  landings: Landing[] = [];
}

// Getters
class LandingGetters extends Getters<LandingState> {
  get landings() {
    return this.state.landings;
  }
  landingById(id: number) {
    return this.state.landings.find(l => l.id === id);
  }
}

// Actions
export const LOAD_ALL_LANDINGS = 'loadAllLandings';
export const CREATE_LANDING = 'createLanding';
export const CREATE_LANDING_LINK = 'createLandingLink';
export const SAVE_LANDING = 'saveLanding';
class LandingActions extends Actions<LandingState, LandingGetters, LandingMutations, LandingActions> {
  [LOAD_ALL_LANDINGS](): void {
    Api.landing
      .all()
      .then((response) => {
        if (!response.success) {
          throw new Error(response.error);
        }

        this.commit(SET_ALL_LANDINGS, response.data);
      })
      .catch((e) => e);
  }

  [CREATE_LANDING](): Promise<number | undefined> {
    return new Promise((resolve) => {
      Api.landing.create()
        .then((response) => {
          if (!response.success) {
            throw new Error(response.error);
          }

          this.commit(ADD_LANDING, response.data);
          resolve(response.data.id);
        })
        .catch((e) => {
          resolve(undefined);
          appInstance.$notifications.error("Ошибка создания лендинга<br>" + e.message)
        });
    });
  }

  [CREATE_LANDING_LINK](landingId: number): void {
    Api.landing.link
      .create(landingId)
      .then((response) => {
        if (!response.success) {
          throw new Error(response.error);
        }

        this.commit(ADD_LINK, { landingId: landingId, link: response.data });
      })
      .catch((e) => appInstance.$notifications.error("Ошибка создания ссылки<br>" + e.message));
  }

  [SAVE_LANDING](landing: Landing): void {
    Api.landing
      .save(landing)
      .then((response) => {
        if (!response.success) {
          throw new Error(response.error);
        }
      })
      .catch((e) => appInstance.$notifications.error("Ошибка сохранения лендинга<br>" + e.message));
  }
}

// Mutations
export const SET_ALL_LANDINGS = "setAllLandings";
export const ADD_LANDING = "addLanding";
export const ADD_LINK = "addLink";
class LandingMutations extends Mutations<LandingState> {
  [SET_ALL_LANDINGS](payload: Landing[]): void {
    this.state.landings = payload;
  }

  [ADD_LANDING](payload: Landing): void {
    this.state.landings.push(payload);
  }

  [ADD_LINK](payload: { landingId: number, link: LandingLink }): void {
    const landing = this.state.landings.find(l => l.id === payload.landingId);
    if (!landing) {
      throw new Error(`Landing #${payload.landingId} not found`);
    }

    landing.links.push(payload.link);
  }
}

// Create a module with module asset classes
export const landingStore = new Module({
  namespaced: true,
  state: LandingState,
  getters: LandingGetters,
  actions: LandingActions,
  mutations: LandingMutations,
});
