<template>
  <div v-if="configurationList && configurationList.length > 0">
    <p>Capability Configuration:</p>
    <ul>
      <li 
        v-for="(value, key) in configurationList" 
        :key="key"
        draggable="true"
        style="cursor: move"
        :class="{ 'drag-over': key === draggedOverIndex }"
        @dragstart="handleDragStart(key, $event)"
        @dragover="handleDragOver(key, $event)"
        @dragleave="handleDragLeave(key, $event)"
        @drop="handleDrop(key, $event)"
        @dragend="handleDragEnd"
      >
        <span v-if="value.necessary">
          <input 
            type="checkbox" 
            disabled 
            :checked="1"
          >
          <label for="checkbox"><strong>{{ value.name }}</strong></label>
          <i> necessary</i>
        </span>
        <span v-else>
          <input 
            type="checkbox" 
            :checked="value.checked"
          >
          <label for="checkbox"><strong>{{ value.name }}:</strong></label>
        </span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useStore } from 'vuex'

const store = useStore()
const configurationList = ref([]);
const props = defineProps({
  selectedcapability: {
    type: Object,
    default: null,
  },
});
const draggedOverIndex = ref(null);

watch(() => props.selectedcapability, async () => {
  if (props.selectedcapability && props.selectedcapability.json) {
    configurationList.value = JSON.parse(props.selectedcapability.json)
  } else {
    configurationList.value = null
  }
}, { deep: true } );

watch(() => configurationList.value, async () => {
  saveConfigurationList(configurationList.value)
}, { deep: true } );

let draggedItemIndex = null;

const handleDragStart = (index, event) => {
  draggedItemIndex = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', draggedItemIndex);
}

const handleDragOver = (index, event) => {
  event.preventDefault();
  draggedOverIndex.value = index;
}

const handleDragLeave = () => {
  draggedOverIndex.value = null;
}

const saveConfigurationList = (configurationList) => {
  if (configurationList != null) {
    const index = store.state.configlist.findIndex(obj => obj.id === props.selectedcapability.id
      && obj.capability === props.selectedcapability.capability);
    if (index !== -1) {
      store.state.configlist[index].json = JSON.stringify(configurationList)
    }
  }
}

const handleDrop = (index, event) => {
  event.preventDefault();
  const droppedItemIndex = event.dataTransfer.getData('text/plain');
  const itemToMove = configurationList.value[droppedItemIndex];
  const currentIndex = configurationList.value.findIndex(item => item.id === index);
  configurationList.value.splice(droppedItemIndex, 1);
  configurationList.value.splice(currentIndex, 0, itemToMove);
  draggedOverIndex.value = null;
}

const handleDragEnd = () => {
  draggedItemIndex = null;
}
</script>

<style scoped>
li.drag-over {
  background-color: #cbc7c7;
  border-bottom: 2px dashed #333;
}
</style>
