<template>
  <div>
    <p>This is an example of a new components in VueJs</p>
    #{ article.title }#
  </div>
</template>
<script>
export default {
  name: "Example",
  data () {
    return {
      articles: [{title: "Voici le nom d'un article"}]
    }
  }
}
</script>
<style scoped>
</style>
