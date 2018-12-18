#include <iostream>  
#include <memory.h>  
#include <string>  
#include <stdio.h>
using namespace std;

string getCmdRs(string cmd){
    string rs = "";
    char buffer[80];
    FILE *fp=popen(cmd.data(),"r");
    while(!feof(fp)){
      fgets(buffer,sizeof(buffer),fp);
      rs+=buffer;
      memset(buffer,'\0',sizeof(buffer));
    }
    pclose(fp);
    return rs;

}

int main(int argc,char *argv[])  
{  
    if(argc < 3) return 1;
    string user = argv[1], ip = argv[2];
    cout << getCmdRs("ssh "+user+"@"+ip+" 'top -b -n 1 -u "+user+"'");
    return 0;  
}  

