import Requestor from "@/common/service/requestor";

export default class PaymentApi {
  service = {
    all: () => Requestor.get('/payment/allServices'),
    durations: {
      all: (serviceId: number) => Requestor.get('/payment/allServiceDurations', { serviceId: serviceId }),
    },
    paid: {
      all: () => Requestor.get('/payment/allPaidServices'),
      create: (serviceDurationId: number) => Requestor.post('/payment/createPaidService', { serviceDurationId: serviceDurationId })
    }
  };

  invoice = {
    all: () => Requestor.get('/payment/allInvoices')
  };
}
