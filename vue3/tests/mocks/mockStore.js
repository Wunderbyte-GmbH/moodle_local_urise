// mockStore.js

import { createStore } from 'vuex';

const mockState = {
  view: 'default', // or any default state you want to set
  strings: {
    fromlearningtitel: 'Objectives Title',
    goalnameplaceholder: 'Enter Objective Name',
    fromlearningdescription: 'Objective Description',
    goalsubjectplaceholder: 'Enter Objective Description',
  },
  learningpath: {
    name: 'Testing',
    description: 'Testing description',
  }
};

const store = createStore({
  state() {
    return mockState;
  },
});

export default store;