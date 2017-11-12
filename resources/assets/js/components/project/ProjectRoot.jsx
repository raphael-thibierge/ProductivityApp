import React from 'react';
import ProjectRender from './ProjectRender.jsx';
import Goal from '../goal/Goal';
import GoalsGraph from '../goal/GoalsGraph.jsx';
import ScoreGoal from '../scoreGoal/scoreGoal.jsx';
import ResponsiveSideBar from '../generic/ResponsiveSideBar.jsx';
import NewProjectRoot from '../project/NewProjectRoot.jsx';
import BudgetRoot from '../budget/BudgetRoot';
import ExpenseRoot from '../expense/ExpenseRoot';

/**
 * Main component managing goals
 */
export default class ProjectRoot extends React.Component {


    constructor(props){
        super(props);
        this.state = this.getInitialState();
        this.projectMap={};
    }

    /**
     * Define component initial state
     *
     * @returns {{goals: Array}}
     */
    getInitialState(){
        return {
            projects: [],
            budgets: [],
            expenses: [],
            newProjectCollapseOpen: false,
            newProjectTitle: '',
            view: 'stats',
        };
    }

    /**
     * Method called when component is mounted in html
     * Loads goal list in AJAX
     */
    componentDidMount(){
        this.request();
        $.get('/financial-data')
            .catch(error => {
                alert('Failed to load app...');
                console.error(error);
            })
            .then(responseJSON => {
                if (responseJSON.status === 'success'){
                    // get response data
                    const data = responseJSON.data;

                    this.setState({
                        loaded: true,
                        budgets: data.budgets,
                        expenses: data.expenses,
                    });
                }
            });
    }

    onBudgetCreated(budget){
        let budgets = this.state.budgets;
        budgets.push(budget);
        this.setState({
            budgets: budgets
        });
    }

    handleSelect(eventKey) {
        event.preventDefault();
        this.setState({
            tab: eventKey
        });
    }

    onBudgetDeleted(budgetId){

        let budgets = [];

        this.state.budgets.forEach((budget) => {
            if (budget._id !== budgetId){
                budgets.push(budget);
            }
        });

        this.setState({
            budgets: budgets
        });

    }

    onBudgetEdited(budgetEdited){

        let budgets = [];

        this.state.budgets.forEach((budget) => {
            if (budget._id === budgetEdited._id){
                budgets.push(budgetEdited);
            } else {
                budgets.push(budget);
            }
        });

        this.setState({
            budgets: budgets
        });

    }

    /**
     * AJAX request to get goals from server
     */
    request(){
        const request = $.ajax({
            url: './projects',
            cache: false,
            method: 'GET',
            success: this.onSuccess.bind(this),
            error: (error) => {console.error(error.message); alert(error)},
        });
    }



    /**
     * AJAX goal loading success method that store returned goals in component state
     * @param response
     */
    onSuccess(response) {
        if (response.status && response.status === 'success'){

            let projects = response.data.projects;

            if (projects.length === 0){
                this.setState({
                    view: 'new_project',
                    projects: []
                })
            }


            for (let projectIterator=0; projectIterator < projects.length; projectIterator++ ){

                this.projectMap[projects[projectIterator]._id] = projectIterator;

                let goals = projects[projectIterator].goals;

                for (let goalIterator=0; goalIterator < goals.length; goalIterator++){

                    let goal = new Goal(goals[goalIterator]);
                    goal.forceUpdate = this.forceUpdate.bind(this);
                    goal.project_id = projects[projectIterator]._id;
                    goal.remove = this.deleteGoal.bind(this, goal);
                    goals[goalIterator] = goal;
                }

                projects[projectIterator].goals = goals;
            }


            this.setState({
                projects: projects
            });
        }
    }

    deleteGoal(goalToDelete) {
        $.ajax({
            url: goalToDelete.routes.destroy,
            cache: false,
            method: 'POST',
            datatype: 'json',
            data: {
                method: 'DELETE',
                _method: 'DELETE',
                _token: window.token,
            },
            success: function (goal) {

                let projects = this.state.projects;

                const project_id = goal.project_id;
                let project = projects[this.projectMap[project_id]];
                const oldGoals= project.goals;

                let goals = [];
                for (let goalIterator=0; goalIterator < project.goals.length; goalIterator++){
                    if (project.goals[goalIterator]._id !== goal._id){
                        goals.push(project.goals[goalIterator]);
                    }
                }

                project.goals = goals;
                projects[this.projectMap[project_id]] = project;

                this.setState({
                    projects: projects
                });

            }.bind(this, goalToDelete),
            error: this.onError,
        });

    }

    addGoal(title, score, project_id) {

        const url = './goals';

        $.ajax({
            url: url,
            cache: false,
            method: 'POST',
            datatype: 'json',
            data: {
                title: title,
                score: score,
                project_id: project_id,
                _token: window.token,
            },
            success: function (response) {


                if (response.status === 'success') {

                    let goal = response.data.goal;

                    let projects = this.state.projects;

                    const project_id = goal.project_id;
                    let project = projects[this.projectMap[project_id]];

                    goal = new Goal(goal);
                    goal.forceUpdate = this.forceUpdate.bind(this);
                    goal.remove = this.deleteGoal.bind(this, goal);

                    project.goals.push(goal);
                    projects[this.projectMap[project_id]] = project;

                    this.setState({
                        projects: projects
                    });
                }

            }.bind(this),
            error: this.onError,
        });


    }


    onNewProjectClick(title) {
        const url = 'projects';
        $.ajax({
            url: url,
            cache: false,
            method: 'POST',
            datatype: 'json',
            data: {
                title: title,
                _token: window.token
            },
            success: function (response) {

                if (response.status === 'success') {

                    let project = response.data.project;

                    let projects = this.state.projects;
                    this.projectMap[project._id] = projects.length;
                    projects.push(project);

                    this.setState({
                        view: project._id,
                        projects: projects,
                        newProjectCollapseOpen: false,
                        newProjectTitle: '',
                    });
                }

            }.bind(this),
            error: (error)=> {alert('Creating project failed'); console.error(error)},
        });

    }

    viewRender(){
        const view = this.state.view;

        if (typeof view === 'undefined')
            return null;

        let viewPathParts = view.split('/');
        if (viewPathParts.length === 2){

            switch (viewPathParts[0]){
                case 'projects':
                    const project = this.state.projects[this.projectMap[viewPathParts[1]]];
                    return <ProjectRender
                        project={project}
                        createGoal={this.addGoal.bind(this)}
                        onTitleChange={this.editProjectTitle.bind(this)}
                    />;
                    break;

                default:
                    return null;
                    break;
            }
        }

        switch (view){
            case 'stats':
                return <GoalsGraph
                    projectCurrentNumber={this.state.projects.length}
                    ref="goalGraph"/>;
                break;

            case 'new_project':
                return <NewProjectRoot
                    onNewProjectClick={this.onNewProjectClick.bind(this)}
                    projectCurrentNumber={this.state.projects.length}
                />;
                break;

            case 'important':
                let importantProject = {
                    _id: 'today',
                    title: 'Important Goals',
                    goals: []
                };

                this.state.projects.map((project) => {
                    importantProject.goals = importantProject.goals.concat(project.goals.filter((goal) => {
                        return goal.today === true && goal.is_completed === false;
                    }));
                });

                return <ProjectRender
                    project={importantProject}
                    />;
                break;

            case 'all_goals':
                let allGoalsProject = {
                    _id: 'all_goals',
                    title: 'All goals',
                    goals: []
                };

                this.state.projects.map((project) => {project.goals.map(goal => {
                    allGoalsProject.goals.push(goal);
                })});

                return <ProjectRender
                    project={allGoalsProject}
                    />;
                break;

            case 'budgets':
                return <BudgetRoot
                    hide
                    budgets={this.state.budgets}
                    onCreate={this.onBudgetCreated.bind(this)}
                    onDelete={this.onBudgetDeleted.bind(this)}
                    onEdit={this.onBudgetEdited.bind(this)}
                />;
                break;

            case 'expenses':
                return <ExpenseRoot
                    expenses={this.state.expenses}
                />;
                break;

            default:
                return null;
        }
    }


    editProjectTitle(title, project_id){

        let projects = this.state.projects;

        let project = projects[this.projectMap[project_id]];

        project.title = title;

        projects[this.projectMap[project_id]] = project;

        this.setState({
            projects: projects
        });
    }


    /**
     * Render method, returning HTML code for goal input and list
     *
     * @returns {XML}
     */
    render() {

        return (
            <div className="row">


                <div className=" col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <ResponsiveSideBar
                        projects={this.state.projects}
                        onItemSelection={(selected) => {if (selected !== 'projects') this.setState({view: selected})}}
                        selected={this.state.view}
                    />
                </div>

                <div className="col-xs-12 col-sm-9">

                    {this.state.view !== 'expenses' && this.state.view !== 'budgets' ? (
                        <ScoreGoal/>
                    ): null}


                    {this.viewRender()}

                </div>
            </div>
        );
    }

};