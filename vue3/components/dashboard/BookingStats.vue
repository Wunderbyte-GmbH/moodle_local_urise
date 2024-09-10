<template>
  <div>
    <div v-if="bookingstats.json && bookingstats.json.booking">
      <h5>{{ store.state.strings.vuedashboardbookinginstances }}</h5>
      <table class="table mt-2">
        <thead class="thead-light">
          <tr>
            <th>{{ store.state.strings.vuedashboardchecked }}</th>
            <th>{{ store.state.strings.vuedashboardname }}</th>
            <th>{{ store.state.strings.vuebookingstatsbookingoptions }}</th>
            <th>{{ store.state.strings.vuebookingstatsbooked }}</th>
            <th>{{ store.state.strings.vuebookingstatswaiting }}</th>
            <th>{{ store.state.strings.vuebookingstatsreserved }}</th>
            <th v-if="showRealParticipants">{{ store.state.strings.vuebookingstatsrealparticipants }}</th>
            <th v-if="showRealCosts">{{ store.state.strings.vuebookingstatsrealcosts }}</th>
            <th v-if="showParticipated">{{ store.state.strings.vuebookingstatsparticipated }}</th>
            <th v-if="showExcused">{{ store.state.strings.vuebookingstatsexcused }}</th>
            <th v-if="showNoShow">{{ store.state.strings.vuebookingstatsnoshow }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="bookingStat in bookingstats.json.booking" :key="'bookingstats' + bookingStat.id">
            <td>
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" :id="'checkbox_' + bookingStat.id"
                  :checked="bookingStat.checked" @change="handleCheckboxChange(bookingStat)">
                <label class="custom-control-label" :for="'checkbox_' + bookingStat.id"></label>
              </div>
              <!-- <input
              :id="'checkbox_' + bookingStat.id"
              type="checkbox"
              class="form-check-input mr-2" role="switch"
              :checked="bookingStat.checked"
              @change="handleCheckboxChange(bookingStat)"
            > -->
            </td>
            <td>
              <a :href="'/mod/booking/view.php?id=' + bookingStat.id">
                {{ bookingStat.name }}
              </a>
            </td>
            <td>{{ bookingStat.bookingoptions }}</td>
            <td>{{ bookingStat.booked }}</td>
            <td>{{ bookingStat.waitinglist }}</td>
            <td>{{ bookingStat.reserved }}</td>
            <td v-if="showRealParticipants">{{ bookingStat.realparticipants }}</td>
            <td v-if="showRealCosts">{{ bookingStat.realcosts }}</td>
            <td v-if="showParticipated">{{ bookingStat.participated }}</td>
            <td v-if="showExcused">{{ bookingStat.excused }}</td>
            <td v-if="showNoShow">{{ bookingStat.noshows }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="bookingstats.courses && bookingstats.courses.length > 0" class="courses mt-3">
        <h5 class="mb-4">{{ store.state.strings.courses }} <a role="button" data-toggle="collapse" href="#collapseCourses"
            aria-expanded="false" aria-controls="collapseCourses"><i class="fa-solid fa-square-caret-down"></i></a></h5>
        <div class="collapse" id="collapseCourses">
          <template v-if="bookingstats.id">
            <a role="button" :href="'/course/edit.php?category=' + bookingstats.id"
              class="mb-3 block-link btn button-secondary">
              <i class="fa-solid fa-plus mr-1" />
              {{ store.state.strings.vuedashboardnewcourse }}
            </a>
          </template>
          <input type="text" v-model="searchTerm" placeholder="Search courses..." class="form-control mb-3 searchCourse" />
          <ul class="list-group list-group-flush">
            <li v-for="course in filteredCourses" :key="course.id" class="list-group-item">
              <a class="" role="button" :href="`/course/view.php?id=${course.id}`">{{
                course.fullname
              }}</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

import { useStore } from 'vuex'
import { computed, ref } from 'vue';

const store = useStore();
const searchTerm = ref('');

const props = defineProps({
  bookingstats: {
    type: Object,
    default: null,
  },
});

const filteredCourses = computed(() => {
  if (!props.bookingstats.courses) return [];
  const term = searchTerm.value.toLowerCase();
  return props.bookingstats.courses.filter(course => course.fullname.toLowerCase().includes(term));
});

const handleCheckboxChange = async (bookingStat) => {
  await store.dispatch('setCheckedBookingInstance', bookingStat)
}

const showRealParticipants = computed(() => {
  return props.bookingstats.json.booking.some(stat => stat.realparticipants > 0);
});

const showRealCosts = computed(() => {
  return props.bookingstats.json.booking.some(stat => stat.realcosts > 0);
});

const showParticipated = computed(() => {
  return props.bookingstats.json.booking.some(stat => stat.participated > 0);
});

const showExcused = computed(() => {
  return props.bookingstats.json.booking.some(stat => stat.excused > 0);
});

const showNoShow = computed(() => {
  return props.bookingstats.json.booking.some(stat => stat.noshows > 0);
});


</script>



<style lang="scss" scoped>
@import './scss/custom.scss';

.thead-light th {
  background: $vuelightcontent;
}

.searchCourse {
  min-width: fit-content;
  width: 500px;
}
</style>
