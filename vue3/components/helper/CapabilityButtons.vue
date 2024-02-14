<template>
  <div class="mt-4">
    <div v-if="!showButtons">
      <p><strong>Capability: {{ choosenCapability.capability }}</strong></p>
      <div class="row mt-2">
        <div class="col-md-12">
          <button 
            class="btn btn-secondary"
            @click="showButtons = true; handleCapabilityClick(null)" 
          >
            Back
          </button>
        </div>
      </div>
    </div>
    <div v-else>
      <p><strong>Capabilites</strong></p>
      <div class="row">
        <div class="col-md-12">
          <button
            v-for="capability in configCapability"
            :key="capability.id" 
            class="btn btn-outline-primary mr-2 mb-2"
            @click="showButtons = false; handleCapabilityClick(capability)"
          >
            {{ capability.capability }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, defineEmits } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const configCapability = ref([])
const showButtons = ref(true)
const choosenCapability = ref(null)

const emit = defineEmits(['capabilityClicked'])

onMounted(() => {
  configCapability.value = store.state.configlist
});

watch(() => store.state.configlist, async () => {
  configCapability.value = store.state.configlist
}, { deep: true } );

watch(() => choosenCapability.value, async () => {
  if (choosenCapability.value == null) {
    emit('capabilityClicked', null)
  }else {
    emit('capabilityClicked', choosenCapability.value)
  }
}, { deep: true } );

const handleCapabilityClick = (capability) => {
  choosenCapability.value = capability;
}
</script>
