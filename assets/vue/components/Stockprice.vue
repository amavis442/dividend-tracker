<template>
  <div>
    <span v-if="price > marketPrice" class="text-danger">{{
      formattedMarketPrice
    }}</span>
    <span v-else-if="price < marketPrice" class="text-success">{{
      formattedMarketPrice
    }}</span>
    <span v-else>{{ formattedMarketPrice }}</span>

    <span v-if="price < marketPrice" class="badge badge-success">
      <i class="fas fa-arrow-circle-up" />
      {{ formattedDiffPrice }}
    </span>

    <span v-else-if="price > marketPrice" class="badge badge-danger">
      <i class="fas fa-arrow-circle-down" />
      {{ formattedDiffPrice }}
    </span>

    <span v-else class="badge badge-info">
      <i class="fas fa-equals" />
      {{ formattedDiffPrice }}
    </span>
    <br />

    <span v-if="result > 0" class="badge badge-success">{{
      formattedResult
    }}</span>
    <span v-else-if="result < 0" class="badge badge-danger">{{
      formattedResult
    }}</span>
    <span v-else class="badge badge-secondary">{{ formattedResult }}</span>

    <span class="badge badge-info">{{ dividendYield }}
      <i v-if="isBuyOppertunity" class="fas fa-shopping-cart" /></span>
  </div>
</template>

<script>
export default {
  name: "Stockprice",
  inject: ["apiBaseUrl"],
  props: {
    stock: {
      type: String,
      default: "",
      require: true,
    },
    prices: {
      type: Object,
      default: [],
      require: false,
    },
    price: {
      type: Number,
      default: 0,
      require: true,
    },
    netdividend: {
      type: Number,
      default: 0,
      require: false,
    },
    freq: {
      type: Number,
      default: 0,
      require: false,
    },
    totalshares: {
      type: Number,
      default: 0,
      require: false,
    },
    dividendTreshold: {
      type: Number,
      default: 0.03,
      require: false,
    },
    maximumAllocationReached: {
      type: Boolean,
      default: false,
      require: false,
    },
  },
  data: function () {
    return {
      marketPrice: 0,
      formattedMarketPrice: null,
      diffPrice: null,
      formattedDiffPrice: null,
      dividendYield: null,
      result: null,
      formattedResult: null,
      isBuyOppertunity: false,
    };
  },
  mounted: function () {
    this.getStockprice();
    setInterval(this.getStockprice, 3000);
  },
  methods: {
    getStockprice: async function () {
      new Promise((resolve, reject) => {
        if (this.$parent.prices && this.$parent.prices.length > 0) {
          resolve(this.$parent.prices);
        }
        reject('I do not have the prices yet');
      })
        .then((result) => {
          for (let i = 0; i < result.length; i++) {
            if (result[i].symbol == this.stock) {
              return result[i];
            }
          }
          Promise.reject('Symbol not found: ' + this.stock);
        })
        .then((result) => {
          this.marketPrice = result.price;
          this.formattedMarketPrice = new Intl.NumberFormat("nl-NL", {
            style: "currency",
            currency: "EUR",
          }).format(this.marketPrice);

          this.diffPrice = this.marketPrice - this.price;
          this.formattedDiffPrice = new Intl.NumberFormat("nl-NL", {
            style: "currency",
            currency: "EUR",
          }).format(this.diffPrice);
          this.dividendYield = "0 %";
          this.isBuyOppertunity = false;
          if (this.marketPrice) {
            var dividendYield =
              (this.freq * this.netdividend) / this.marketPrice;
            this.dividendYield = new Intl.NumberFormat("nl-NL", {
              style: "percent",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(dividendYield);
            //console.log(this.stock);
            //console.log(dividendYield);
            //console.log(this.dividendTreshold);

            if (this.dividendTreshold <= dividendYield) {
              this.isBuyOppertunity = true;
            }
            /* console.log(
              "Maximum allocation reached: " + this.maximumAllocationReached
            ); */
            if (this.maximumAllocationReached) {
              this.isBuyOppertunity = false;
            }
            //console.log(this.isBuyOppertunity);
          }
          this.result = this.totalshares * this.diffPrice;
          this.formattedResult = new Intl.NumberFormat("nl-NL", {
            style: "currency",
            currency: "EUR",
          }).format(this.result);
        })

        //.catch(console.log.bind(console));
        .catch((error) => {
          console.error(error);
        })
    },
  },
};
</script>
<style scoped>
</style>
