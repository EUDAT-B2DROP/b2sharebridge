<script>
export default {
  props: {
    id: { required: true, type: Number },
    files: {required: true, type: Number },
    status: {required: true, type: String },
    url: {required: true, type: URL },
    triggered_at: {required: true, type: Date },
    last_update: {required: true, type: Date },
  },
  data() {
    return {
      id: this.id,
      status: this.status,
      url: this.url,
    };
  },
};
</script>