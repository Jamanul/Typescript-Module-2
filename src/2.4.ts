{
    interface Developer<T,X=null> {
        name : string;
        employed:boolean;
        salary:number;
        dependency: {
            family:boolean;
            otherJob:boolean
        };
        watch: T,
        bike?:X
    }

    interface SamsungWatch{
        name:string,
        model:number
    }


    const poorDev :Developer<SamsungWatch> ={
        name:"sakib",
        employed:false,
        salary:0,
        dependency:{
            family:false,
            otherJob:true
        },
        watch:{
            name:'samsung',
            model:2020
        }
    }

    interface Dukati {
        name:string,
        wheels:number
    }


    const richDev:Developer<SamsungWatch,Dukati> ={
        name:"sakib" ,
        employed:false,
        salary:100,
        dependency:{
            family:false,
            otherJob:true
        },
        watch:{
            name:'samsung',
            model:2020
        },
        bike:{
            name:"dukati",
            wheels:2
        }
    }


}