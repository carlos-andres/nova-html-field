import IndexField from './components/IndexField.vue'
import DetailField from './components/DetailField.vue'
import FormField from './components/FormField.vue'

Nova.booting((app) => {
    app.component('IndexHtmlField', IndexField)
    app.component('DetailHtmlField', DetailField)
    app.component('FormHtmlField', FormField)
})
