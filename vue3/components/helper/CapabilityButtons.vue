<template>
  <div class="mt-4">
    <div v-if="!showButtons">
      <p><strong>Capability: (LOKALIZE!) {{ choosenCapability.name }}</strong></p>
      <div class="row mt-2">
        <div class="col-md-12">
          <button
            class="btn btn-secondary mr-2"
            @click="showButtons = true; handleCapabilityClick(null)"
          >
            Back (LOKALIZE!)
          </button>
          <button
            class="btn btn-primary"
            @click="saveContent"
          >
            Save (LOKALIZE!)
          </button>
        </div>
      </div>
    </div>
    <div v-else>
      <p><strong>Capabilites (LOKALIZE!)</strong></p>
      <div class="row">
        <div class="col-md-12">
          <button
            v-for="capability in configCapability"
            :key="capability.id"
            class="btn btn-outline-primary mr-2 mb-2"
            @click="showButtons = false; handleCapabilityClick(capability)"
          >
            {{ capability.name }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, defineEmits } from 'vue'
import { notify } from "@kyvg/vue3-notification"
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

const saveContent = async () => {
  const result = await store.dispatch('setParentContent', choosenCapability.value)
  if(result.status == 'success'){
    notify({
      title: 'Configuration was saved',
      text: 'Configuration was saved successfully.',
      type: 'success'
    });
  }else {
    notify({
      title: 'Configuration was saved',
      text: 'Something went wrong while saving. The changes have not been changed.',
      type: 'warn'
    });
  }
}
</script>
