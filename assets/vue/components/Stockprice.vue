<template>
  <div>
    <span
      v-if="price > marketPrice"
      class="text-danger"
    >{{ formattedMarketPrice }}</span>
    <span
      v-else-if="price < marketPrice"
      class="text-success"
    >{{ formattedMarketPrice }}</span>
    <span
      v-else
    >{{ formattedMarketPrice }}</span>
 
    <span
      v-if="price < marketPrice"
      class="badge badge-success"
    >
      <i class="fas fa-arrow-circle-up" /> 
      {{ formattedDiffPrice }}
    </span>

    <span
      v-else-if="price > marketPrice"
      class="badge badge-danger"
    >
      <i class="fas fa-arrow-circle-down" /> 
      {{ formattedDiffPrice }}
    </span>

    <span
      v-else
      class="badge badge-info"
    >
      <i class="fas fa-equals" />
      {{ formattedDiffPrice }}
    </span>
    <br>
    
    <span
      v-if="result > 0"
      class="badge badge-success"
    >{{ formattedResult }}</span>
    <span
      v-else-if="result < 0"
      class="badge badge-danger"
    >{{ formattedResult }}</span>
    <span
      v-else
      class="badge badge-secondary"
    >{{ formattedResult }}</span>
    
    <span class="badge badge-info">{{ dividendYield }}</span>
  </div>
</template>
<script>
export default {
  name: "Stockprice",
  props: {
    'stock': {
      type: String,
      default: '',
      require: true
    },
    price: {
      type: Number,
      default: 0,
      require: true
    },
    netdividend: {
      type: Number,
      default: 0,
      require: false
    },
    freq: {
      type: Number,
      default: 0,
      require: false
    },
    totalshares: {
      type: Number,
      default: 0,
      require: false
    }
  },
  data: function() {
    return {
      marketPrice: null,
      formattedMarketPrice: null,
      diffPrice: null,
      formattedDiffPrice: null,
      dividendYield: null,
      result: null,
      formattedResult: null
    }
  },
  mounted: function() {
    this.getStockprice();
    setInterval(
      this.getStockprice
      , 60000);
  },
  methods: {
    getStockprice: function() {
      fetch(this.$apiBaseUrl + 'api/stock_prices/' + this.stock).then(resp => resp.json())
        .then(result => {
          this.marketPrice = result.price;
          this.formattedMarketPrice = new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(this.marketPrice);
          
          this.diffPrice = this.marketPrice - this.price;
          this.formattedDiffPrice =  new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(this.diffPrice);
          this.dividendYield = '0 %';
          if (this.marketPrice) {
            var dividendYield = (this.freq * this.netdividend) / this.marketPrice;
            this.dividendYield = new Intl.NumberFormat('nl-NL', { style: 'percent', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(dividendYield);
          } 
          this.result = this.totalshares * this.diffPrice;
          this.formattedResult =  new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(this.result);
        }).catch(console.log.bind(console));
    }
  }
}
</script>
<style scoped>
</style>
