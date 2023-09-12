const admin = {
	username: 'admin',
	password: 'password',
	store: {
		firstname: 'John',
		lastname: 'Doe',
		company: 'Automattic',
		country: 'US:CA',
		addressfirstline: 'addr 1',
		addresssecondline: 'addr 2',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94107',
	},
};

const customer = {
	username: 'customer',
	password: 'password',
	billing: {
		firstname: 'John',
		lastname: 'Doe',
		company: 'Automattic',
		country: 'US',
		countryName: 'United States',
		addressfirstline: 'addr 1',
		addresssecondline: 'addr 2',
		city: 'San Francisco',
		state: 'CA',
		stateName: 'California',
		postcode: '94107',
		phone: '123456789',
		email: 'john.doe@example.com',
	},
	shipping: {
		firstname: 'John',
		lastname: 'Doe',
		company: 'Automattic',
		country: 'US',
		addressfirstline: 'addr 1',
		addresssecondline: 'addr 2',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94107',
	},
};

module.exports = {
	admin,
	customer,
	baseUrl: 'http://localhost:8889'
};
