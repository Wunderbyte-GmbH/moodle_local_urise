<template>
  <div v-if="bookingstats.json && bookingstats.json.booking">
    <h5>{{store.state.strings.vuedashboardbookinginstances}}</h5>
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
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="bookingStat in bookingstats.json.booking"
          :key="'bookingstats' + bookingStat.id"
        >
          <td>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" :id="'checkbox_' + bookingStat.id"
              :checked="bookingStat.checked"
              @change="handleCheckboxChange(bookingStat)">
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
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>

  import { useStore } from 'vuex'
  import { computed } from 'vue';
  const store = useStore();

  const props = defineProps({
    bookingstats: {
      type: Object,
      default: null,
    },
  });

  const handleCheckboxChange = async (bookingStat) => {
    await store.dispatch('setCheckedBookingInstance', bookingStat)
  }

  const showRealParticipants = computed(() => {
    console.log('participans', props.bookingstats.json.booking.some(stat => stat.realparticipants > 0));
    return props.bookingstats.json.booking.some(stat => stat.realparticipants > 0);
  });

  const showRealCosts = computed(() => {
    return props.bookingstats.json.booking.some(stat => stat.realcosts > 0);
  });
</script>



<style lang="scss" scoped>
 @import './scss/custom.scss';

  .thead-light th {
    background: $vuelightcontent;
  }
</style>
