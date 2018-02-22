import React from 'react';
import ExpenseTable from './ExpenseTable';
import Tools from '../Tools'
import CreateFinancialTransactionModal from "../financial/CreateFinancialTransactionModal";
import {Glyphicon, FormGroup} from 'react-bootstrap';
import Datetime from 'react-datetime';

export default class ExpenseRoot extends React.Component {

    constructor(props) {
        super(props);
        let startDate = new Date();
        startDate.setSeconds(0);
        startDate.setMinutes(0);
        startDate.setHours(0);
        startDate.setMonth(startDate.getMonth() -1);

        this.state = {
            start_date: startDate,
            end_date: null,
            transactions: [],
        }

    }

    onSave(transaction){
        let transactions = this.state.transactions;
        transactions.push(transaction);
        this.setState({transactions: transactions});
    }

    onUpdate(transaction){
        let transactions = this.state.transactions;

        for (let i = 0; i < transactions.length; i++){

            if (transactions[i]._id === transaction._id){
                transactions[i] = transaction;
            }
        }

        this.setState({
            transactions: transactions
        });

    }

    componentDidMount(){
        $.get('/financial-transactions')
            .catch(error => {
                alert(error.statusText);
                console.error(error);
            })
            .then(responseJSON => {
                if (responseJSON.status === 'success'){
                    // get response data
                    const transactions = responseJSON.data.transactions;

                    function totalTransactions() {
                        let total = 0;
                        for ( let i = 0, _len = this.length; i < _len; i++ ) {

                            if (this[i].type === 'entrance'){
                                total += this[i].price;
                            } else if (this[i].type === 'expense'){
                                total -= this[i].price;
                            }

                        }
                        return parseFloat(total.toFixed(2));
                    }

                    transactions.__proto__.totalTransactions = totalTransactions;

                    this.setState({
                        loaded: true,
                        transactions: transactions,
                    });
                }
            });
    }

    filter(){
        let transactions = this.state.transactions.filter((expense) => {

            // expense created at
            var expense_date = Tools.dateFormater(expense.created_at);
            expense_date.setMinutes(expense_date.getMinutes() - expense_date.getTimezoneOffset());

            if (this.state.start_date !== null){
                const start = new Date(this.state.start_date);
                if ( expense_date < start){
                    return false;
                }
            }

            if (this.state.end_date !== null){

                const end = new Date(this.state.end_date);

                if ( expense_date > end){
                    return false;
                }
            }
            return true;
        });

        transactions = transactions.sort(function (transactionA, transactionB) {
            if (Tools.dateFormater(transactionA.created_at) > Tools.dateFormater(transactionB.created_at)){
                return 1;
            } else if (Tools.dateFormater(transactionA.created_at) < Tools.dateFormater(transactionB.created_at)){
                return -1;
            }
            return 0;
        });

        return transactions;

    }

    render(){

        const transactions = this.filter();

        return (
            <div className="row">
                <div className="col-xs-12">

                    <div className="row">
                        <div className="col-xs-12">
                            <h1 className="page-header finance-page-header">Expenses</h1>
                        </div>
                    </div>

                    <div className="row">
                        <div className="col-xs-12">
                            <FormGroup>
                                <CreateFinancialTransactionModal
                                    onSave={this.onSave.bind(this)}
                                />
                            </FormGroup>
                        </div>
                    </div>

                    {this.state.transactions.length > 0 ? (
                        <div className="row text-center">
                            <div className="col-xs-6">
                                <FormGroup>
                                    <Datetime
                                        onChange={(day) => {this.setState({start_date: day && day !== '' ? day.toDate(): null})}}
                                        value={this.state.start_date}
                                    />
                                </FormGroup>
                            </div>

                            <div className="col-xs-6">
                                <FormGroup>
                                    <Datetime
                                        onChange={(day) => {this.setState({end_date: day && day !== '' ? day.toDate(): null})}}
                                        value={this.state.end_date}
                                    />
                                </FormGroup>
                            </div>

                            <ExpenseTable
                                expenses={transactions}
                                onUpdate={this.onUpdate.bind(this)}
                            />
                        </div>
                    ): null}

                    <div className="row">
                        <div className="col-xs-12">
                            <div className="alert alert-info">
                                <p><Glyphicon glyph="info-sign"/> When spending money, <strong>ask edgar on messenger</strong> to save it for you :</p>
                                <p><em>"Add a new expense of 10.2 CAD #lunch at #restaurant with #friends"</em></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        );
    }

}
