<template>
    <div class="dashboard">
        <h1>WordPress Vue Admin Dashboard</h1>
        <div class="card">
            <Button label="Click Me" icon="pi pi-check" @click="emitToPhp" />
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import bus from "../bus";

const emitToPhp = () => {
    bus.emit("vue-event", { message: "Hello from Vue!" });
};
</script>
