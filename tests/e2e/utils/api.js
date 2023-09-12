const wcApi = require("@woocommerce/woocommerce-rest-api").default;
const config = require("../playwright.config");

let api;

// Ensure that global-setup.js runs before creating api client
if (process.env.CONSUMER_KEY && process.env.CONSUMER_SECRET) {
  api = new wcApi({
    url: config.use.baseURL,
    consumerKey: process.env.CONSUMER_KEY,
    consumerSecret: process.env.CONSUMER_SECRET,
    version: "wc/v3",
  });
}

/**
 * Allow explicit construction of api client.
 */
const constructWith = (consumerKey, consumerSecret) => {
  api = new wcApi({
    url: config.use.baseURL,
    consumerKey,
    consumerSecret,
    version: "wc/v3",
  });
};

const throwCustomError = (
  error,
  customMessage = "Something went wrong. See details below."
) => {
  throw new Error(
    customMessage
      .concat(
        `\nResponse status: ${error.response.status} ${error.response.statusText}`
      )
      .concat(
        `\nResponse headers:\n${JSON.stringify(
          error.response.headers,
          null,
          2
        )}`
      ).concat(`\nResponse data:\n${JSON.stringify(
      error.response.data,
      null,
      2
    )}
`)
  );
};

const update = {
  order: async (order) => {
    const orderId = order.id;
    delete order.id;
    await api.put(`orders/${orderId}`, order).catch((error) => {
      throwCustomError(
        error,
        "Something went wrong when trying update order∂."
      );
    });
  },
};

const deletePost = {
  product: async (id) => {
    await api.delete(`products/${id}`, {
      force: true,
    });
  },
  products: async (ids) => {
    const res = await api
      .post("products/batch", { delete: ids })
      .then((response) => response)
      .catch((error) => {
        throwCustomError(
          error,
          "Something went wrong when batch deleting products."
        );
      });
    return res.data;
  },
  order: async (id) => {
    await api.delete(`orders/${id}`, {
      force: true,
    });
  },
  orders: async (ids) => {
    const res = await api
      .post("orders/batch", { delete: ids })
      .then((response) => response)
      .catch((error) => {
        throwCustomError(
          error,
          "Something went wrong when batch deleting orders."
        );
      });
    return res.data;
  },
};

module.exports = {
  update,
  deletePost,
  constructWith,
};
