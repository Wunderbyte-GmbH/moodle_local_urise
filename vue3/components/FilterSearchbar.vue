<template>
  <div class="d-flex justify-content-end align-items-center">
    <div
      class="input-group mr-2"
      style="min-width: 200px; margin-bottom: 8px;"
    >
      <input
        type="text"
        class="form-control rounded-pill"
        :placeholder="store.state.strings.vuefiltertabs"
        aria-label="Search"
        aria-describedby="button-addon2"
        @input="handleInputChange"
      >
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex'

const store = useStore()

const filteredTabs = ref([]);
const emits = defineEmits(['filteredTabs']);

const props = defineProps({
  tabs: {
      type: Array,
      default: null,
    },
});

const handleInputChange = (event) => {
  const searchTerm = event.target.value.toLowerCase();
  filteredTabs.value = props.tabs.filter(tab => tab.name.toLowerCase().includes(searchTerm));
  emits('filteredTabs', filteredTabs.value);
}
</script>